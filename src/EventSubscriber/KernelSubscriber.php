<?php

namespace App\EventSubscriber;

use App\Service\AuditService;
use App\Service\MenuService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class KernelSubscriber implements EventSubscriberInterface
{
    private MenuService $menuService;
    private AuditService $auditService;

    public function __construct(
        MenuService $menuService,
        AuditService $auditService
    ) {
        $this->menuService = $menuService;
        $this->auditService = $auditService;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $controller = $event->getController();
        if (isset($controller[0])) {
            $controllerClass = get_class($controller[0]);
            if (method_exists($controller[0], 'setAuditService')) {
                $controller[0]->setAuditService($this->auditService);
            }
            $this->menuService->setCurrentController($controllerClass);
        }
        if (isset($controller[1])) {
            $this->menuService->setCurrentAction($controller[1]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
