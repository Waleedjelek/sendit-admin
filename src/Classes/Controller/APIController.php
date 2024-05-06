<?php

namespace App\Classes\Controller;

use App\Classes\Exception\APIException;
use App\Service\JWTService;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;

class APIController extends BaseController
{
    protected Request $request;

    protected JWTService $JWTService;

    protected array $requestData = [];

    private ?string $cdnURL = null;

    public function initRequestData(): self
    {
        try {
            $this->requestData = $this->request->toArray();
        } catch (JsonException $exception) {
            $this->requestData = [];
        }

        return $this;
    }

    public function getVar(string $name, $default = null)
    {
        if (isset($this->requestData[$name]) && !empty($this->requestData[$name])) {
            return $this->requestData[$name];
        }

        return $default;
    }

    public function getAssetURL(): ?string
    {
        $cdnURL = $this->getCdnURL();
        if (!empty($cdnURL)) {
            return $cdnURL;
        }

        return $this->request->getSchemeAndHttpHost();
    }

    /**
     * @return mixed
     *
     * @throws APIException
     */
    public function getRequiredVar(string $name)
    {
        if (!isset($this->requestData[$name]) || empty($this->requestData[$name])) {
            throw new APIException('Required parameter missing : '.$name, 100);
        }

        return $this->requestData[$name];
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function setRequestData(array $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    public function getJWTService(): JWTService
    {
        return $this->JWTService;
    }

    public function setJWTService(JWTService $JWTService): self
    {
        $this->JWTService = $JWTService;

        return $this;
    }

    public function getCdnURL(): ?string
    {
        if (!is_null($this->cdnURL)) {
            return $this->cdnURL;
        }
        $this->cdnURL = '';
        $cdnURL = $this->getParameter('app_sendit_admin_cdn_url');
        if (!empty($cdnURL)) {
            $this->cdnURL = $cdnURL;
        }

        return $this->cdnURL;
    }
}
