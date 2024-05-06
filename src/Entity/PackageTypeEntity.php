<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_package_types")
 * @UniqueEntity("code")
 * @ExclusionPolicy("all")
 */
class PackageTypeEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=20, name="package_type")
     * @Expose
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     * @Expose
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Expose
     */
    private ?string $description = null;

    private ?string $packageImagePrefix = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Expose
     * @Accessor(getter="getPackageImageURL")
     */
    private ?string $packageImage = null;

    /**
     * @Expose
     */
    private ?string $packageImageContent = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Expose
     * @Accessor(getter="getIconImageURL")
     */
    private ?string $iconImage = null;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $weight = 0.0;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $maxWeight = 0.0;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $length = 0.0;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $width = 0.0;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $height = 0.0;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Expose
     */
    private bool $valueRequired = true;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private int $sortOrder = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $modifiedDate = null;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    private bool $active = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCode(): string
    {
        return strtolower($this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = strtolower($code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPackageImagePrefix(): ?string
    {
        return $this->packageImagePrefix;
    }

    public function setPackageImagePrefix(?string $packageImagePrefix): self
    {
        $this->packageImagePrefix = $packageImagePrefix;

        return $this;
    }

    public function getPackageImage(): ?string
    {
        return $this->packageImage;
    }

    public function setPackageImage(?string $packageImage): self
    {
        $this->packageImage = $packageImage;

        return $this;
    }

    public function getPackageImageContent(): ?string
    {
        return $this->packageImageContent;
    }

    public function setPackageImageContent(?string $packageImageContent): self
    {
        $this->packageImageContent = $packageImageContent;

        return $this;
    }

    public function getPackageImageURL(): ?string
    {
        if (is_null($this->packageImage)) {
            return null;
        }

        if (!is_null($this->packageImagePrefix)) {
            return $this->packageImagePrefix.$this->packageImage;
        }

        return $this->packageImage;
    }

    public function getIconImage(): ?string
    {
        return $this->iconImage;
    }

    public function setIconImage(?string $iconImage): self
    {
        $this->iconImage = $iconImage;

        return $this;
    }

    public function getIconImageURL(): ?string
    {
        if (is_null($this->iconImage)) {
            return null;
        }

        if (!is_null($this->packageImagePrefix)) {
            return $this->packageImagePrefix.$this->iconImage;
        }

        return $this->iconImage;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }

    public function setMaxWeight(float $maxWeight): self
    {
        $this->maxWeight = $maxWeight;

        return $this;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function setLength(float $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function isValueRequired(): bool
    {
        return $this->valueRequired;
    }

    public function setValueRequired(bool $valueRequired): self
    {
        $this->valueRequired = $valueRequired;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

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
