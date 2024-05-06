<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\UserEntity;
use App\Entity\UserTokenEntity;
use App\Repository\UserTokenEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class RefreshTokenService extends BaseService
{
    private string $cookieName = 'refreshToken';

    private int $expireInDays = 30;

    private EntityManagerInterface $entityManager;

    private UserTokenEntityRepository $userTokenEntityRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserTokenEntityRepository $userTokenEntityRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userTokenEntityRepository = $userTokenEntityRepository;
    }

    public function createToken(UserEntity $userEntity): UserTokenEntity
    {
        $userTokenEntity = new UserTokenEntity();
        $userTokenEntity->setUser($userEntity);
        try {
            $random = random_bytes(20);
        } catch (\Exception $e) {
            $random = Uuid::uuid4()->toString();
        }
        $refreshToken = hash('sha512', $random);
        $userTokenEntity->setToken($refreshToken);
        $userTokenEntity->setActive(true);
        $createdDate = new \DateTime();
        $userTokenEntity->setCreatedDate($createdDate);
        $userTokenEntity->setExpiryDate($createdDate->modify("+{$this->expireInDays} days"));
        $this->entityManager->persist($userTokenEntity);
        $this->entityManager->flush();

        return $userTokenEntity;
    }

    public function refreshToken(UserTokenEntity $userTokenEntity): UserTokenEntity
    {
        $createdDate = new \DateTime();
        $userTokenEntity->setExpiryDate($createdDate->modify("+{$this->expireInDays} days"));
        $this->entityManager->flush();

        return $userTokenEntity;
    }

    public function expireToken(?UserTokenEntity $userTokenEntity): ?UserTokenEntity
    {
        $createdDate = new \DateTime();
        $userTokenEntity->setExpiryDate($createdDate->modify("-{$this->expireInDays} days"));
        $userTokenEntity->setActive(false);
        $this->entityManager->flush();

        return $userTokenEntity;
    }

    public function getToken(string $token): ?UserTokenEntity
    {
        $userTokenEntity = $this->userTokenEntityRepository->getByToken($token);

        if (is_null($userTokenEntity)) {
            return null;
        }

        if ($userTokenEntity->getExpiryDate()->getTimestamp() < (new \DateTime())->getTimestamp()) {
            $this->expireToken($userTokenEntity);

            return null;
        }

        return $userTokenEntity;
    }

    public function getExpireInDays(): int
    {
        return $this->expireInDays;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
