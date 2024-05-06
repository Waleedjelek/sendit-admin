<?php

namespace App\Classes\Controller;

use App\Classes\Exception\APIException;
use App\Entity\UserEntity;
use App\Repository\UserEntityRepository;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedAPIController extends APIController
{
    protected UserEntityRepository $userEntityRepository;

    protected UserEntity $user;

    public function initAuthentication(): self
    {
        $bearerToken = $this->getBearerToken();
        if (is_null($bearerToken)) {
            throw new APIException('Unauthorized - Empty Token', 401, Response::HTTP_UNAUTHORIZED);
        }

        try {
            $token = $this->getJWTService()->getToken($bearerToken);
        } catch (\Exception $e) {
            throw new APIException('Unauthorized - Malformed Token', 401, Response::HTTP_UNAUTHORIZED);
        }

        if (false === $this->getJWTService()->validateToken($token)) {
            throw new APIException('Unauthorized - Invalid Token', 401, Response::HTTP_UNAUTHORIZED);
        }

        $userId = $token->claims()->get('userId');
        $userEntity = $this->getUserEntityRepository()->find($userId);
        if (is_null($userEntity)) {
            throw new APIException('Unauthorized - Invalid User', 401, Response::HTTP_UNAUTHORIZED);
        }

        $this->setUser($userEntity);

        return $this;
    }

    public function getBearerToken(): ?string
    {
        if (!$this->request->headers->has('authorization')) {
            return null;
        }
        $bearerString = $this->request->headers->get('authorization');
        if (preg_match('/Bearer\s(\S+)/', $bearerString, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getUserEntityRepository(): UserEntityRepository
    {
        return $this->userEntityRepository;
    }

    public function setUserEntityRepository(UserEntityRepository $userEntityRepository): self
    {
        $this->userEntityRepository = $userEntityRepository;

        return $this;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): self
    {
        $this->user = $user;

        return $this;
    }
}
