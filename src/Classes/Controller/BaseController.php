<?php

namespace App\Classes\Controller;

use App\Service\AuditService;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    protected Serializer $serializer;

    protected ?AuditService $auditService = null;

    private ?\DateTimeZone $currentTimeZone = null;

    protected function dataJson(
        array $data,
        $responseCode = Response::HTTP_OK,
        array $headers = []
    ): Response {
        $json = $this->getSerializer()->serialize([
            'data' => $data,
            'error' => false,
        ], 'json');

        return new JsonResponse($json, $responseCode, $headers, true);
    }

    public function addLog(
        string $module,
        string $action,
        string $description,
        ?string $referenceId = null,
        ?string $type = null
    ) {
        $this->getAuditService()->add($module, $action, $description, $referenceId, $type);
    }

    public function em(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    public function setSerializer(Serializer $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function getAuditService(): ?AuditService
    {
        return $this->auditService;
    }

    public function setAuditService(?AuditService $auditService): void
    {
        $this->auditService = $auditService;
    }

    public function formatToTimezone($dateTime, $format = 'd-M-y h:i A')
    {
        if (!($dateTime instanceof \DateTime)) {
            return $dateTime;
        }
        if (is_null($this->currentTimeZone)) {
            $timezone = 'Asia/Dubai';
            $this->currentTimeZone = new \DateTimeZone($timezone);
        }
        $dateTime->setTimezone($this->currentTimeZone);

        return $dateTime->format($format);
    }
}
