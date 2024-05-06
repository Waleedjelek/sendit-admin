<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_locale")
 * @UniqueEntity("code")
 * @ExclusionPolicy("all")
 */
class LocaleEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=200, unique=true, nullable=false)
     * @Expose
     */
    private string $code;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Expose
     */
    private string $localeText;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $modifiedDate = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLocaleText(): string
    {
        return $this->localeText;
    }

    public function setLocaleText(string $localeText): self
    {
        $this->localeText = $localeText;

        return $this;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getModifiedDate(): ?\DateTime
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate(?\DateTime $modifiedDate): self
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }
}
