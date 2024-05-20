<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\CouponEntity;
use Doctrine\ORM\EntityManagerInterface;

class CouponService extends BaseService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getCouponByCode(string $coupon): ?CouponEntity
    {
        $repo = $this->entityManager->getRepository(CouponEntity::class);

        return $repo->findOneBy(['coupon' => $coupon]);
    }

    public function getCouponArray(CouponEntity $couponEntity): array
    {
        $data = [
            'id' => $couponEntity->getId(),
            'coupon' => $couponEntity->getCoupon(),
            'isActive' => $couponEntity->isActive(),
         ];
        return $data;
    }
  
}
