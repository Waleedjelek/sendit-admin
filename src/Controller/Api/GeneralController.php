<?php

namespace App\Controller\Api;

use App\Classes\CommonData;
use App\Classes\Controller\APIController;
use App\Classes\Exception\APIException;
use App\Entity\CountryEntity;
use App\Entity\LocaleEntity;
use App\Entity\PackageTypeEntity;
use App\Entity\QuoteEntity;
use App\Entity\ZonePriceEntity;
use App\Repository\CountryEntityRepository;
use App\Service\OrderService;
use App\Service\PackageService;
use App\Service\QuoteService;
use App\Service\TrackService;
use App\Service\ZoneService;
use Brick\Postcode\PostcodeFormatter;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/general")
 */
class GeneralController extends APIController
{
    /**
     * @Route("/getQuote", methods={"POST"}).
     */
    public function getQuote(
        ZoneService $zoneService,
        PackageService $packageService,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $sendingType = strtolower($this->getRequiredVar('type'));
        $countryFrom = strtoupper($this->getRequiredVar('from'));
        $countryTo = strtoupper($this->getRequiredVar('to'));
        $packages = $this->getRequiredVar('packages', []);

        if ('domestic' == $sendingType) {
            $searchCountryFrom = 'AE';
            $searchCountryTo = 'AE';
        } else {
            $searchCountryFrom = $countryFrom;
            $searchCountryTo = $countryTo;
        }

        $totalWeight = 0.0;
        $totalValue = 0.0;
        $totalVolumeWeight = 0.0;
        $calculatedWeight = 0.0;
        $listPackageType = [];
        if (!empty($packages)) {
            foreach ($packages as $packageIndex => $package) {
                if (!isset($package['type']) || empty($package['type'])) {
                    throw new APIException('Required parameter missing : type in packages['.$packageIndex.']', 100);
                }
                if (!isset($package['weight']) || empty($package['weight'])) {
                    throw new APIException('Required parameter missing : weight in packages['.$packageIndex.']', 100);
                }
                if (!isset($package['length']) || empty($package['length'])) {
                    throw new APIException('Required parameter missing : length in packages['.$packageIndex.']', 100);
                }
                if (!isset($package['width']) || empty($package['width'])) {
                    throw new APIException('Required parameter missing : width in packages['.$packageIndex.']', 100);
                }
                if (!isset($package['height']) || empty($package['height'])) {
                    throw new APIException('Required parameter missing : height in packages['.$packageIndex.']', 100);
                }
                $packageType = $packageService->getByCode($package['type']);
                if (is_null($packageType)) {
                    throw new APIException('Invalid type in packages['.$packageIndex.']', 101);
                }
                $packages[$packageIndex]['packageType'] = $packageType;
                if ($package['weight'] > $packageType->getMaxWeight()) {
                    throw new APIException('Weight is more than max weight in packages['.$packageIndex.']', 102);
                }
                $listPackageType[] = $packageType->getType();
                if ($packageType->isValueRequired()) {
                    if (!isset($package['value']) || empty($package['value'])) {
                        throw new APIException('Required parameter missing : value in packages['.$packageIndex.']', 100);
                    }
                } else {
                    $package['value'] = 0;
                }

                $packages[$packageIndex]['length'] = ceil($package['length']);
                $packages[$packageIndex]['width'] = ceil($package['width']);
                $packages[$packageIndex]['height'] = ceil($package['height']);
                $packages[$packageIndex]['value'] = ceil($package['value']);
                $packages[$packageIndex]['type'] = strtolower($package['type']);

                $volumeWeight = (ceil($package['length']) * ceil($package['width']) * ceil($package['height'])) / 5000;
                $packages[$packageIndex]['volumeWeight'] = $volumeWeight;

                $totalValue = bcadd($totalValue, $package['value'], 3);
                $totalWeight = bcadd($totalWeight, $package['weight'], 3);
                $totalVolumeWeight = bcadd($totalVolumeWeight, $volumeWeight, 3);
                if ($volumeWeight > $package['weight']) {
                    $calculatedWeight = bcadd($calculatedWeight, $volumeWeight, 3);
                } else {
                    $calculatedWeight = bcadd($calculatedWeight, $package['weight'], 3);
                }
            }
        }

        $listPackageType = array_unique($listPackageType);
        $searchPackageType = 'package';
        if (1 == count($listPackageType)) {
            if ('document' == $listPackageType[0] && $calculatedWeight <= 2.0) {
                $searchPackageType = 'document';
            }
        }

        $countryFromEntity = $countryEntityRepository->getByCode($searchCountryFrom);
        if (is_null($countryFromEntity)) {
            throw new APIException('Invalid from country', 103);
        }

        $countryToEntity = $countryEntityRepository->getByCode($searchCountryTo);
        if (is_null($countryToEntity)) {
            throw new APIException('Invalid from country', 104);
        }

        $quotes = [];
        $priceList = $zoneService->getPrices(
            $searchCountryFrom,
            $searchCountryTo,
            $searchPackageType,
            $calculatedWeight,
            0.0
        );
        if (!empty($priceList)) {
            /**
             * @var $price ZonePriceEntity
             */
            foreach ($priceList as $price) {
                $companyEntity = $price->getZone()->getCompany();
                $companyId = $companyEntity->getId();
                $noteDetail = $companyEntity->getNoteSummary();
                if (!is_null($noteDetail)) {
                    $noteDetail = nl2br($noteDetail);
                }
                $companyEntity->setImagePrefix($this->getAssetURL().'/uploads');
                $quote = [
                    'companyId' => $companyId,
                    'companyName' => $companyEntity->getName(),
                    'companyLogoURL' => $companyEntity->getLogoImageURL(),
                    'companyLogoWidth' => $companyEntity->getLogoWidth(),
                    'minDays' => $price->getZone()->getMinDays(),
                    'maxDays' => $price->getZone()->getMaxDays(),
                    'noteTitle' => $companyEntity->getNoteTitle(),
                    'noteDetail' => $noteDetail,
                    'zone' => $price->getZone()->getCode(),
                    'price' => $price->getPrice(),
                    'boeAmount' => 0,
                    'boeAdded' => false,
                    'packageCount' => 1,
                ];
                if (!isset($quotes[$companyId])) {
                    if (0 != $companyEntity->getBoeThreshold() && 0 != $companyEntity->getBoeAmount()) {
                        if ($totalValue >= $companyEntity->getBoeThreshold()) {
                            $quote['boeAdded'] = true;
                            $quote['boeAmount'] = $companyEntity->getBoeAmount();
                            $quote['price'] = bcadd($quote['price'], $companyEntity->getBoeAmount(), 2);
                        }
                    }
                    $quotes[$companyId] = $quote;
                } else {
                    if (isset($quotes[$companyId])) {
                        $quotes[$companyId]['price'] = bcadd($quotes[$companyId]['price'], $price->getPrice(),
                            2);
                        ++$quotes[$companyId]['packageCount'];
                    }
                }
            }
        }

        $allowedCount = 1;
        $finalQuotes = [];
        $sort = [];
        if ($quotes) {
            foreach ($quotes as $quote) {
                if ($quote['packageCount'] == $allowedCount) {
                    $sort[] = $quote['price'];
                    $quote['price'] = number_format(round($quote['price'], 2), 2);
                    $finalQuotes[] = $quote;
                }
            }
            if (!empty($sort)) {
                array_multisort($sort, SORT_ASC, SORT_NUMERIC, $finalQuotes);
            }
        }

        return $this->dataJson([
            'type' => $sendingType,
            'from' => $countryFrom,
            'to' => $countryTo,
            'totalValue' => $totalValue,
            'totalUserWeight' => $totalWeight,
            'totalVolumeWeight' => $totalVolumeWeight,
            'calculatedWeight' => $calculatedWeight,
            'quotes' => $finalQuotes,
        ]);
    }

    /**
     * @Route("/createQuote", methods={"POST"}).
     */
    public function createQuote(
        QuoteService $quoteService,
        PackageService $packageService,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $contactName = $this->getRequiredVar('contactName');
        $contactMobile = $this->getRequiredVar('contactMobile');
        $contactEmail = $this->getRequiredVar('contactEmail');
        $sendingType = strtolower($this->getRequiredVar('type'));
        $countryFrom = strtoupper($this->getRequiredVar('from'));
        $countryTo = strtoupper($this->getRequiredVar('to'));
        $packages = $this->getRequiredVar('packages', []);

        if ('domestic' == $sendingType) {
            $quoteType = 'dom';
            $searchCountryFrom = 'AE';
            $searchCountryTo = 'AE';
        } else {
            $quoteType = 'int';
            $searchCountryFrom = $countryFrom;
            $searchCountryTo = $countryTo;
        }

        if (!empty($packages)) {
            foreach ($packages as $packageIndex => $package) {
                if (!isset($package['type']) || empty($package['type'])) {
                    throw new APIException('Required parameter missing : type in packages['.$packageIndex.']', 100);
                }
                if (!isset($package['weight']) || empty($package['weight'])) {
                    throw new APIException('Required parameter missing : weight in packages['.$packageIndex.']', 100);
                }
                $packageType = $packageService->getByCode($package['type']);
                if (is_null($packageType)) {
                    throw new APIException('Invalid type in packages['.$packageIndex.']', 101);
                }
                if ($package['weight'] > $packageType->getMaxWeight()) {
                    throw new APIException('Weight is more than max weight in packages['.$packageIndex.']', 102);
                }
            }
        }

        $countryFromEntity = $countryEntityRepository->getByCode($searchCountryFrom);
        if (is_null($countryFromEntity)) {
            throw new APIException('Invalid from country', 103);
        }

        $countryToEntity = $countryEntityRepository->getByCode($searchCountryTo);
        if (is_null($countryToEntity)) {
            throw new APIException('Invalid from country', 104);
        }

        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation(),
        ]);

        if (false === $validator->isValid($contactEmail, $multipleValidations)) {
            throw new APIException('Invalid email', 105);
        }

        $runIndex = $quoteService->getRunIndex();
        $quoteId = $quoteService->generateQuoteId($runIndex);

        $quoteEntity = new QuoteEntity();
        $quoteEntity->setSourceCountry($countryFromEntity);
        $quoteEntity->setDestinationCountry($countryToEntity);
        $quoteEntity->setQuoteId($quoteId);
        $quoteEntity->setRunIndex($runIndex);
        $quoteEntity->setPackageInfo($packages);
        $quoteEntity->setType($quoteType);
        if ('dom' == $quoteType) {
            $quoteEntity->setSourceState($countryFrom);
            $quoteEntity->setDestinationState($countryTo);
        }
        $quoteEntity->setContactName($contactName);
        $quoteEntity->setContactEmail($contactEmail);
        $quoteEntity->setContactMobile($contactMobile);
        $quoteEntity->setStatus('New');
        $quoteEntity->setCreatedDate(new \DateTime());

        $this->em()->persist($quoteEntity);
        $this->em()->flush();

        $quoteService->sendCustomerEmail($quoteEntity);
        $quoteService->sendAdminNotificationEmail($quoteEntity);

        return $this->dataJson([
            'quoteId' => $quoteEntity->getQuoteId(),
        ]);
    }

    /**
     * @Route("/countries", methods={"GET"}).
     */
    public function countries(
        Packages $assetsManager,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $formatter = new PostcodeFormatter();
        $qb = $countryEntityRepository->createQueryBuilder('m');
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);
        $qb->andWhere(' m.active = 1 ');

        $qb->add('orderBy', 'm.sortOrder DESC, m.name ASC ');
        $result = $qb->getQuery()->getResult();

        $countries = [];

        if (!empty($result)) {
            /**
             * @var $countryEntity CountryEntity
             */
            foreach ($result as $countryEntity) {
                $ca['code'] = $countryEntity->getCode();
                $ca['name'] = $countryEntity->getName();
                $ca['dialCode'] = '+'.$countryEntity->getDialCode();
                $ca['zipCodeRequired'] = $formatter->isSupportedCountry($countryEntity->getCode());
                $flagImage = $assetsManager->getUrl('build/images/flags/'.$countryEntity->getFlag());
                $flagImage = str_replace('/build/images/flags/', '', $flagImage);
                $ca['flag'] = $flagImage;
                $ca['active'] = $countryEntity->isActive();
                $countries[] = $ca;
            }
        }

        return $this->dataJson([
            'flagPrefix' => $this->getAssetURL().'/build/images/flags/',
            'countries' => $countries,
        ]);
    }

    /**
     * @Route("/tracking", methods={"POST"}).
     */
    public function tracking(
        OrderService $orderService,
        TrackService $trackService
    ): Response {
        $orderId = $this->getRequiredVar('orderId');

        $userOrderEntity = $orderService->getOrderByOrderId($orderId);
        if (is_null($userOrderEntity)) {
            throw new APIException('Order not found', 101);
        }

        $shouldUpdate = true;

        if (!is_null($userOrderEntity->getTrackingFetchedDate())) {
            $lastUpdatedTime = $userOrderEntity->getTrackingFetchedDate();
            $currentTime = new \DateTime();
            $interval = $currentTime->getTimestamp() - $lastUpdatedTime->getTimestamp();
            if ($interval < 7200) {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            $trackInfo = $trackService->fetchTrackingInfo($userOrderEntity);
            if (!empty($trackInfo)) {
                $userOrderEntity->setTrackingInfo($trackInfo);
                $userOrderEntity->setTrackingFetchedDate(new \DateTime());
                $this->em()->flush();
            }
        }

        $trackingEvents = [];
        $trackingInfo = $userOrderEntity->getTrackingInfo();
        if (!empty($trackingInfo)) {
            if (isset($trackingInfo['z1']) && !empty($trackingInfo['z1'])) {
                foreach ($trackingInfo['z1'] as $item) {
                    $trackingEvents[] = [
                        'time' => $item['a'],
                        'location' => $item['c'],
                        'description' => $item['z'],
                    ];
                }
            }
        }

        return $this->dataJson([
            'from' => $userOrderEntity->getSourceCountry()->getName(),
            'to' => $userOrderEntity->getDestinationCountry()->getName(),
            'events' => $trackingEvents,
        ]);
    }

    /**
     * @Route("/locales", methods={"GET"}).
     */
    public function locales(): Response
    {
        $qb = $this->em()->getRepository(LocaleEntity::class)->createQueryBuilder('l');
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);

        $qb->add('orderBy', 'l.code ASC');
        $result = $qb->getQuery()->getResult();

        $locales = [];

        if (!empty($result)) {
            /**
             * @var $localeEntity LocaleEntity
             */
            foreach ($result as $localeEntity) {
                $locales[$localeEntity->getCode()] = $localeEntity->getLocaleText();
            }
        }

        return $this->dataJson([
            'locales' => $locales,
        ]);
    }

    /**
     * @Route("/states", methods={"GET"}).
     */
    public function states(): Response
    {
        $data = [
            'uaeStates' => CommonData::getStates(),
        ];

        return $this->dataJson($data);
    }

    /**
     * @Route("/packageTypes", methods={"GET"}).
     */
    public function packageTypes(PackageService $packageService): Response
    {
        $qb = $this->em()->getRepository(PackageTypeEntity::class)->createQueryBuilder('pt');
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);
        $qb->andWhere(' pt.active = 1 ');

        $qb->add('orderBy', ' pt.sortOrder ASC ');
        $packageTypes = $qb->getQuery()->getResult();

        if (!empty($packageTypes)) {
            /**
             * @var $packageType PackageTypeEntity
             */
            foreach ($packageTypes as $packageType) {
                $packageType->setPackageImageContent($packageService->getImageContent($packageType->getPackageImage()));
                $packageType->setPackageImagePrefix($this->getAssetURL().'/uploads');
            }
        }

        $data = [
            'packageTypes' => $packageTypes,
        ];

        return $this->dataJson($data);
    }

    /**
     * @Route("/quoteData", methods={"GET"}).
     */
    public function quoteData(
        Packages $assetsManager,
        CountryEntityRepository $countryEntityRepository,
        PackageService $packageService
    ): Response {
        $countries = [];
        $formatter = new PostcodeFormatter();

        $qb = $this->em()->getRepository(CountryEntity::class)->createQueryBuilder('c');
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);
        $qb->andWhere(' c.active = 1 ');

        $qb->add('orderBy', 'c.sortOrder DESC, c.name ASC ');
        $result = $qb->getQuery()->getResult();

        if (!empty($result)) {
            /**
             * @var $countryEntity CountryEntity
             */
            foreach ($result as $countryEntity) {
                $ca['code'] = $countryEntity->getCode();
                $ca['name'] = $countryEntity->getName();
                $ca['dialCode'] = '+'.$countryEntity->getDialCode();
                $ca['zipCodeRequired'] = $formatter->isSupportedCountry($countryEntity->getCode());
                $flagImage = $assetsManager->getUrl('build/images/flags/'.$countryEntity->getFlag());
                $flagImage = str_replace('/build/images/flags/', '', $flagImage);
                $ca['flag'] = $flagImage;
                $ca['active'] = $countryEntity->isActive();
                $countries[] = $ca;
            }
        }

        $qb = $this->em()->getRepository(PackageTypeEntity::class)->createQueryBuilder('pt');
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);
        $qb->andWhere(' pt.active = 1 ');

        $qb->add('orderBy', ' pt.sortOrder ASC ');
        $packageTypes = $qb->getQuery()->getResult();

        if (!empty($packageTypes)) {
            /**
             * @var $packageType PackageTypeEntity
             */
            foreach ($packageTypes as $packageType) {
                $packageType->setPackageImageContent($packageService->getImageContent($packageType->getPackageImage()));
                $packageType->setPackageImagePrefix($this->getAssetURL().'/uploads');
            }
        }

        $data = [
            'packageTypes' => $packageTypes,
            'uaeStates' => CommonData::getStates(),
            'flagPrefix' => $this->getAssetURL().'/build/images/flags/',
            'countries' => $countries,
        ];

        return $this->dataJson($data);
    }
}
