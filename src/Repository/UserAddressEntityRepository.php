<?php

namespace App\Repository;

use App\Entity\UserAddressEntity;
use App\Entity\UserEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserAddressEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAddressEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAddressEntity[]    findAll()
 * @method UserAddressEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAddressEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAddressEntity::class);
    }

    public function getUserActiveAddresses(UserEntity $userEntity): array
    {
        return $this->findBy([
            'user' => $userEntity,
            'active' => 1,
        ], [
            'name' => 'ASC',
            'createdDate' => 'ASC',
        ]);
    }
}
