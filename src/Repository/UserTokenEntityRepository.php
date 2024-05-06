<?php

namespace App\Repository;

use App\Entity\UserTokenEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserTokenEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserTokenEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserTokenEntity[]    findAll()
 * @method UserTokenEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserTokenEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTokenEntity::class);
    }

    public function getByToken(string $token): ?UserTokenEntity
    {
        return $this->findOneBy([
            'token' => $token,
            'active' => 1,
        ]);
    }
}
