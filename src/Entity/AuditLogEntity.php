<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_audit_logs")
 * @ExclusionPolicy("all")
 */
class AuditLogEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private string $type;

    /**
     * @ORM\Column(type="guid", nullable=true)
     */
    private ?string $referenceId;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private string $module;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private string $action;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $description;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private string $ip;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserEntity", inversedBy="addresses", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?UserEntity $user;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private \DateTime $actionDate;

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): void
    {
        $this->user = $user;
    }

    public function getActionDate(): \DateTime
    {
        return $this->actionDate;
    }

    public function setActionDate(\DateTime $actionDate): void
    {
        $this->actionDate = $actionDate;
    }
}
