<?php

namespace App\Entity;

use App\Repository\CountryEntityRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=CountryEntityRepository::class)
 * @ORM\Table(name="sendit_countries")
 * @UniqueEntity("code")
 * @ExclusionPolicy("all")
 */
class CountryEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=10, unique=true)
     * @Expose
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Expose
     */
    private ?string $dialCode = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private ?string $flag = null;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private ?int $sortOrder = 0;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Expose
     */
    private bool $active = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return strtoupper($this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDialCode(): ?string
    {
        return $this->dialCode;
    }

    public function setDialCode(?string $dialCode): self
    {
        $this->dialCode = $dialCode;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): self
    {
        $this->flag = $flag;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
