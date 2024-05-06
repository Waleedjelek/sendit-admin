<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\AuditLogEntity;
use App\Entity\UserEntity;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class AuditService extends BaseService
{
    private Kernel $kernel;

    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        Kernel $kernel,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function add(
        string $module,
        string $action,
        string $description,
        ?string $referenceId = null,
        ?string $type = null
    ) {
        $audit = new AuditLogEntity();

        if (is_null($type)) {
            $type = 'Normal';
        }

        $audit->setType($type);
        $audit->setModule($module);
        $audit->setAction($action);
        $audit->setDescription($description);
        $audit->setReferenceId($referenceId);

        $audit->setIp($this->getUserIP());
        $audit->setActionDate(new \DateTime());

        $user = $this->getUser();
        if (is_object($user) && UserEntity::class == get_class($user)) {
            $audit->setUser($user);
        }

        $this->entityManager->persist($audit);
        $this->entityManager->flush();
    }

    public function getUserIP(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (is_null($request)) {
            return '127.0.0.1';
        }
        $ip = $request->getClientIp();
        if (is_null($ip)) {
            return '127.0.0.1';
        }

        return $ip;
    }

    public function getUser()
    {
        return $this->security->getUser();
    }
}
