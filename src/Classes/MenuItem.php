<?php

namespace App\Classes;

class MenuItem
{
    private string $name;

    private ?string $caption;

    private ?string $url;

    private ?string $controllerClass;

    private ?string $iconClass;

    private string $userRole;

    private bool $active = false;

    public function __construct(
        string $name,
        string $caption,
        string $url,
        string $controllerClass,
        string $iconClass,
        string $userRole = 'ROLE_EDITOR'
    ) {
        $this->name = $name;
        $this->caption = $caption;
        $this->url = $url;
        $this->controllerClass = $controllerClass;
        $this->iconClass = $iconClass;
        $this->userRole = $userRole;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getControllerClass(): ?string
    {
        return $this->controllerClass;
    }

    public function setControllerClass(?string $controllerClass): void
    {
        $this->controllerClass = $controllerClass;
    }

    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    public function setIconClass(?string $iconClass): void
    {
        $this->iconClass = $iconClass;
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function setUserRole(string $userRole): void
    {
        $this->userRole = $userRole;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
