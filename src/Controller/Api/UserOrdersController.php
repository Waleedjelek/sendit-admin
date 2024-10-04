<?php

namespace App\Controller\Api;

use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Entity\UserOrderEntity;
use App\Entity\ZonePriceEntity;
use App\Service\AddressService;
use App\Service\CompanyService;
use App\Service\OrderService;
use App\Service\PackageService;
use App\Service\ZoneService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/api/v1/orders")
 */
class UserOrdersController extends AuthenticatedAPIController
{
    /**
     * @Route("/list", methods={"POST"}).
     */
    public function list(OrderService $orderService): Response
    {
        $userEntity = $this->getUser();

        $userOrders = $orderService->getUserOrders($userEntity);

        $data['orders'] = [];
        if (!empty($userOrders)) {
            foreach ($userOrders as $order) {
                $data['orders'][] = $orderService->getOrderArray($order, $this->getAssetURL());
            }
        }

        return $this->dataJson($data);
    }

    /**
     * @Route("/get", methods={"POST"}).
     *
     * @throws APIException
     */
    public function getInfo(
        OrderService $orderService
    ): Response {
        $userEntity = $this->getUser();

        $orderId = $this->getRequiredVar('orderId');

        $userOrderEntity = $orderService->getOrderById($orderId, $userEntity);
        if (is_null($userOrderEntity)) {
            throw new APIException('Invalid order', 101);
        }

        $data['order'] = $orderService->getOrderArray($userOrderEntity, $this->getAssetURL());

        return $this->dataJson($data);
    }

    /**
     * @Route("/add", methods={"POST"}).
     *
     * @throws APIException
     */
    public function add(
        CompanyService $companyService,
        AddressService $addressService,
        PackageService $packageService,
        ZoneService $zoneService,
        OrderService $orderService
    ): Response {
        $userEntity = $this->getUser();

        $collectionDate = $this->getRequiredVar('collectionDate');
        $collectionTime = $this->getRequiredVar('collectionTime');
        $selectedCompanyId = $this->getRequiredVar('selectedCompanyId');
        $collectionAddressId = $this->getRequiredVar('collectionAddressId');
        $destinationAddressId = $this->getRequiredVar('destinationAddressId');
        $successRedirectURL = $this->getRequiredVar('successRedirectURL');
        $failureRedirectURL = $this->getRequiredVar('failureRedirectURL');
        $insurancePrice = $this->getRequiredVar('insurancePrice');
        $contactForInsurance = $this->getVar('contactForInsurance', 'no');
        $packages = $this->getRequiredVar('packages', []);
        $couponCode = $this->getVar('coupon');
        $percent = $this->getVar('discount');

        $collectionDateTime = \DateTime::createFromFormat('Y-m-d', $collectionDate);

        $selectedCompany = $companyService->getCompanyById($selectedCompanyId);
        if (is_null($selectedCompany)) {
            throw new APIException('Invalid selected company', 101);
        }

        $collectionAddress = $addressService->getAddressById($collectionAddressId, $userEntity);
        if (is_null($collectionAddress)) {
            throw new APIException('Invalid collection address', 102);
        }

        $destinationAddress = $addressService->getAddressById($destinationAddressId, $userEntity);
        if (is_null($destinationAddress)) {
            throw new APIException('Invalid destination address', 103);
        }

        $loadedPackages = [];
        $totalValue = 0.0;
        $totalWeight = 0.0;
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
                    throw new APIException('Invalid type in packages['.$packageIndex.']', 104);
                }
                $loadedPackages[$packageType->getCode()] = $packageType;
                if ($package['weight'] > $packageType->getMaxWeight()) {
                    throw new APIException('Weight is more than max weight in packages['.$packageIndex.']', 105);
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

        $currentDatetime = new \DateTime();
        $currentDateStamp = strtotime($currentDatetime->format('Y-m-d'));
        $collectionDateStamp = strtotime($collectionDateTime->format('Y-m-d'));
        if ($collectionDateStamp < $currentDateStamp) {
            throw new APIException('Invalid collectionDate. Only future date allowed', 106);
        }

        $validator = Validation::createValidator();
        $violations = $validator->validate($successRedirectURL, new Url());
        if (0 !== count($violations)) {
            throw new APIException('Invalid success redirect url', 107);
        }
        $violations = $validator->validate($failureRedirectURL, new Url());
        if (0 !== count($violations)) {
            throw new APIException('Invalid failure redirect url', 108);
        }

        $countryFrom = $addressService->getCountryByCode($collectionAddress->getCountryCode());
        $countryTo = $addressService->getCountryByCode($destinationAddress->getCountryCode());
        $orderType = $zoneService->getCompanyType($countryFrom->getCode(), $countryTo->getCode());
        $orderMethod = $zoneService->getSendMethod($countryFrom->getCode(), $countryTo->getCode());

        $priceList = [];
        $totalPrice = 0.0;
        $prices = $zoneService->getPrices(
            $countryFrom->getCode(),
            $countryTo->getCode(),
            $searchPackageType,
            $calculatedWeight,
            0.0,
            $selectedCompany
        );
        if (!empty($prices)) {
            /**
             * @var $price ZonePriceEntity
             */
            foreach ($prices as $price) {
                $priceList[] = $price;
                $totalPrice = bcadd($totalPrice, $price->getPrice(), 2);
            }
        }

        $boeAmount = 0.0;
        if (0 != $selectedCompany->getBoeThreshold() && $totalValue >= $selectedCompany->getBoeThreshold()) {
            $boeAmount = $selectedCompany->getBoeAmount();
            $totalPrice = bcadd($totalPrice, $selectedCompany->getBoeAmount(), 2);
        }

        $collectionAddressArray = $this->getSerializer()->toArray($collectionAddress);
        $destinationAddressArray = $this->getSerializer()->toArray($destinationAddress);
        $priceListArray = $this->getSerializer()->toArray($priceList);
        $runIndex = $orderService->getRunIndex();
        $orderId = $orderService->generateOrderId($runIndex);
        $orderStatus = 'Draft';
        $oldPrice =0;
        $discounted = '';
        if(!empty($couponCode) && (int)$percent > 0){
            $oldPrice=$totalPrice;
            $discountPrice = $totalPrice / 100;
            $totalPrice =  $totalPrice - $discountPrice * (int)$percent;
            $totalPrice =  sprintf("%.2f", $totalPrice);
            $discounted='Coupon code applied';
        }else{
            $couponCode = '';
            $discounted = '';

        }

        if(!empty($insurancePrice) && (float)$insurancePrice > 0){
            $totalPrice =  $totalPrice + $insurancePrice;
        }
        
        $userOrderEntity = new UserOrderEntity();
        $userOrderEntity->setCouponCode($couponCode);
        $userOrderEntity->setDiscounted($discounted);
            $userOrderEntity->setUser($userEntity);
        $userOrderEntity->setSourceCountry($countryFrom);
        $userOrderEntity->setDestinationCountry($countryTo);
        $userOrderEntity->setSelectedCompany($selectedCompany);
        $userOrderEntity->setRunIndex($runIndex);
        $userOrderEntity->setOrderId($orderId);
        $userOrderEntity->setType($orderType);
        if (is_null($orderMethod)) {
            $userOrderEntity->setMethod('local');
        } else {
            $userOrderEntity->setMethod($orderMethod);
        }
        $userOrderEntity->setCollectionDate($collectionDateTime);
        $userOrderEntity->setCollectionTime($collectionTime);
        $userOrderEntity->setCollectionAddressId($collectionAddressId);
        $userOrderEntity->setCollectionAddress($collectionAddressArray);
        $userOrderEntity->setDestinationAddressId($destinationAddressId);
        $userOrderEntity->setDestinationAddress($destinationAddressArray);
        $userOrderEntity->setPackageInfo($packages);
        $userOrderEntity->setPriceInfo($priceListArray);
        $userOrderEntity->setTotalWeight($totalWeight);
        $userOrderEntity->setTotalVolumeWeight($totalVolumeWeight);
        $userOrderEntity->setFinalWeight($calculatedWeight);
        $userOrderEntity->setTotalPrice($totalPrice);
        $userOrderEntity->setTotalValue($totalValue);
        $userOrderEntity->setBoeAmount($boeAmount);
        $userOrderEntity->setStatus($orderStatus);
        if ('yes' == strtolower($contactForInsurance)) {
            $userOrderEntity->setContactForInsurance(true);
        } else {
            $userOrderEntity->setContactForInsurance(false);
        }
        $userOrderEntity->setPaymentStatus('Pending');
        $userOrderEntity->setSuccessRedirectURL($successRedirectURL);
        $userOrderEntity->setFailureRedirectURL($failureRedirectURL);
        $userOrderEntity->setCreatedDate($currentDatetime);
        $this->em()->persist($userOrderEntity);
        $this->em()->flush();

        $paymentURL = $this->generateUrl(
            'app_payment_redirect',
            ['id' => $userOrderEntity->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->dataJson([
            'orderId' => $orderId,
            'oldPrice'=>$oldPrice,
            'total' => $totalPrice,
            'status' => $orderStatus,
            'paymentURL' => $paymentURL,
        ]);
    }
}
