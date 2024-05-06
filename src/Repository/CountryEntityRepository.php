<?php

namespace App\Repository;

use App\Entity\CountryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CountryEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method CountryEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method CountryEntity[]    findAll()
 * @method CountryEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountryEntity::class);
    }

    /**
     * @return CountryEntity[]
     */
    public function getAll(): array
    {
        return $this->findBy([], [
            'sortOrder' => 'DESC',
            'name' => 'ASC',
        ]);
    }

    public function getByCode(string $code): ?CountryEntity
    {
        $qb = $this->createQueryBuilder('c');
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
