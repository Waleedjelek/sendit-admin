<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\CountryEntity;
use App\Entity\UserAddressEntity;
use App\Entity\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class AddressService extends BaseService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getAddressById(string $companyId, UserEntity $userEntity): ?UserAddressEntity
    {
        $repo = $this->entityManager->getRepository(UserAddressEntity::class);

        return $repo->findOneBy([
            'id' => $companyId,
            'user' => $userEntity,
        ]);
    }

    public function getCountryByCode(string $code): ?CountryEntity
    {
        $repo = $this->entityManager->getRepository(CountryEntity::class);
        $qb = $repo->createQueryBuilder('c');
        $qb->where('upper(c.code) = upper(:code)');
        $qb->setParameter('code', $code);
        $qb->setMaxResults(1);
        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
        }

        return null;
    }
}
