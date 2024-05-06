<?php

namespace App\EventSubscriber;

use App\Classes\Controller\APIController;
use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Repository\UserEntityRepository;
use App\Service\JWTService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class APISubscriber implements EventSubscriberInterface
{
    private KernelInterface $kernel;

    private JWTService $JWTService;

    private UserEntityRepository $userEntityRepository;

    private SerializerInterface $serializer;

    /**
     * APISubscriber constructor.
     */
    public function __construct(
        KernelInterface $kernel,
        JWTService $JWTService,
        UserEntityRepository $userEntityRepository,
        SerializerInterface $serializer
    ) {
        $this->kernel = $kernel;
        $this->JWTService = $JWTService;
        $this->userEntityRepository = $userEntityRepository;
        $this->serializer = $serializer;
    }

    /**
     * @throws APIException
     */
    public function onKernelController(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $controller = $event->getController();
        if (!isset($controller[0])) {
            return;
        }
        $activeController = $controller[0];
        if (!$activeController instanceof APIController) {
            return;
        }
        /* @var APIController $activeController */
        $activeController
            ->setSerializer($this->serializer)
            ->setJWTService($this->JWTService)
            ->setRequest($event->getRequest())
            ->initRequestData();
        if ($activeController instanceof AuthenticatedAPIController) {
            $activeController
                ->setUserEntityRepository($this->userEntityRepository)
                ->initAuthentication();
        }
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ('json' !== $event->getRequest()->getContentType()) {
            if (!$exception instanceof APIException) {
                return;
            }
        }

        $data = [
            'data' => false,
            'error' => [
                'api' => false,
                'code' => $exception->getCode(),
                'exception' => true,
                'message' => $exception->getMessage(),
            ],
        ];
        $status = Response::HTTP_OK;

        switch (true) {
            case $exception instanceof APIException:
                $data['error']['api'] = true;
                break;
            case $exception instanceof BadRequestHttpException:
                $data['error']['code'] = 400;
                $data['error']['message'] = 'Bad request!';
                $status = Response::HTTP_BAD_REQUEST;
                break;
            case $exception instanceof MethodNotAllowedHttpException:
                $data['error']['code'] = 405;
                $data['error']['message'] = 'Bad method!';
                $status = Response::HTTP_METHOD_NOT_ALLOWED;
                break;
            case $exception instanceof NotFoundHttpException:
                $data['error']['code'] = 404;
                $data['error']['message'] = 'Not found!';
                $status = Response::HTTP_NOT_FOUND;
                break;
        }

        $json = $this->serializer->serialize($data, 'json');
        $event->allowCustomResponseCode();
        $event->setResponse(new JsonResponse($json, $status, [], true));
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
            'kernel.exception' => 'onKernelException',
        ];
    }
}
