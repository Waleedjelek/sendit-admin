<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\CompanyEntity;
use App\Entity\CountryEntity;
use App\Entity\ZoneEntity;
use App\Entity\ZonePriceEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class ZoneService extends BaseService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function importZonePrices(
        ZoneEntity $zoneEntity,
        string $priceType,
        string $priceFor,
        array $priceRecords
    ) {
        $qs = "delete from App\Entity\ZonePriceEntity zp where zp.zone = '".$zoneEntity->getId()."' ";
        $qs .= " AND zp.type = '{$priceType}' ";
        $qs .= " AND zp.for = '{$priceFor}' ";
        $query = $this->entityManager->createQuery($qs);
        $numDeleted = $query->execute();

        $createdDate = new \DateTime();
        foreach ($priceRecords as $record) {
            $zonePriceEntity = new ZonePriceEntity();
            $zonePriceEntity->setZone($zoneEntity);
            $zonePriceEntity->setType($priceType);
            $zonePriceEntity->setFor($priceFor);
            $zonePriceEntity->setWeight($record['weight']);
            $zonePriceEntity->setPrice($record['price']);
            $zonePriceEntity->setCreatedDate($createdDate);
            $this->entityManager->persist($zonePriceEntity);
        }
        $this->entityManager->flush();
    }

    public function getSendMethod(string $countryFrom, string $countryTo): ?string
    {
        if ('AE' == $countryFrom && 'AE' != $countryTo) {
            return 'export';
        }
        if ('AE' != $countryFrom && 'AE' == $countryTo) {
            return 'import';
        }
        if ('AE' == $countryFrom && 'AE' == $countryTo) {
            return 'local';
        }

        return 'na';
    }

    public function getCompanyType(string $countryFrom, string $countryTo): string
    {
        if ('AE' == $countryFrom && 'AE' == $countryTo) {
            return 'dom';
        }

        return 'int';
    }

    public function searchZoneCountryUnique(
        CountryEntity $countryEntity,
        CompanyEntity $selectedCompany,
        ?ZoneEntity $zoneEntity = null
    ): array {
        $repo = $this->entityManager->getRepository(ZoneEntity::class);

        $qb = $repo->createQueryBuilder('zone');
        $qb->andWhere(':country MEMBER OF zone.countries');
        $qb->setParameter('country', $countryEntity->getId());
        $qb->andWhere(" zone.company = '".$selectedCompany->getId()."' ");
        if (!empty($zoneEntity)) {
            $qb->andWhere(" zone.id != '".$zoneEntity->getId()."' ");
        }
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);

        return $qb->getQuery()->getResult();
    }

    public function getPrices(
        string $countryFrom,
        string $countryTo,
        string $packageType,
        float $weight,
        float $volumeWeight,
        ?CompanyEntity $selectedCompany = null
    ): array {
        $companyType = $this->getCompanyType($countryFrom, $countryTo);
        $priceMethod = $this->getSendMethod($countryFrom, $countryTo);

        $searchCountry = $countryTo;
        if ('AE' != $countryFrom && 'AE' == $countryTo) {
            $searchCountry = $countryFrom;
        }

        $searchWeight = $weight;
        if ($volumeWeight > $weight) {
            $searchWeight = $volumeWeight;
        }

        $repo = $this->entityManager->getRepository(ZoneEntity::class);
        $qb = $repo->createQueryBuilder('zone');
        $qb->innerJoin('zone.company', 'company');
        $qb->innerJoin('zone.countries', 'country');
        $qb->andWhere(" company.type = '".$companyType."' ");
        $qb->andWhere(" country.code = '".$searchCountry."' ");
        if (!is_null($selectedCompany)) {
            $qb->andWhere(" company.id = '".$selectedCompany->getId()."' ");
        }
        $qb->andWhere(" country.active = '1' ");
        $qb->andWhere(" zone.active = '1' ");
        $qb->andWhere(" company.active = '1' ");
        $qb->setFirstResult(0);
        $qb->setMaxResults(999);
        $availableZones = $qb->getQuery()->getResult();

        $priceList = [];
        if (!empty($availableZones)) {
            /** @var ZoneEntity $zoneEntity */
            foreach ($availableZones as $zoneEntity) {
                $zoneIds[] = $zoneEntity->getId();

                $qb = $this->entityManager->getRepository(ZonePriceEntity::class)->createQueryBuilder('zp');
                $qb->setFirstResult(0);
                $qb->setMaxResults(999);
                $qb->andWhere("zp.zone = '".$zoneEntity->getId()."'");
                $qb->andWhere("zp.for = '".$packageType."'");
                if (!is_null($priceMethod)) {
                    $qb->andWhere("zp.type = '".$priceMethod."'");
                }
                $qb->andWhere("zp.weight >= {$searchWeight}");
                $qb->add('orderBy', ' zp.weight ASC ');
                $qb->setMaxResults(1);

                try {
                    $priceEntity = $qb->getQuery()->getOneOrNullResult();
                    if (!is_null($priceEntity)) {
                        $priceList[] = $priceEntity;
                    }
                } catch (NonUniqueResultException $exception) {
                }
            }
        }

        return $priceList;
    }
}
