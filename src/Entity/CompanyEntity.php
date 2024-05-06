<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_companies")
 * @UniqueEntity("code")
 * @ExclusionPolicy("all")
 */
class CompanyEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=20, name="company_type")
     * @Expose
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=10, unique=true, nullable=false)
     * @Expose
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $carrierCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Expose
     */
    private ?string $noteTitle = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Expose
     */
    private ?string $noteSummary = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Expose
     * @Accessor(getter="getLogoImageURL")
     */
    private ?string $logoImage = null;

    private ?string $imagePrefix = null;

    /**
     * @ORM\Column(type="integer", options={"default":64})
     * @Expose
     */
    private ?int $logoWidth = 64;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     * @Expose
     */
    private float $boeThreshold = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     * @Expose
     */
    private float $boeAmount = 0.0;

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
     * @Expose
     */
    private bool $active = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ZoneEntity", mappedBy="company", fetch="LAZY")
     */
    private Collection $zones;

    /**
     * @param string $id
     */
    public function __construct()
    {
        $this->zones = new ArrayCollection();
    }

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return strtoupper($this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getCarrierCode(): ?string
    {
        return $this->carrierCode;
    }

    public function setCarrierCode(?string $carrierCode): self
    {
        $this->carrierCode = $carrierCode;

        return $this;
    }

    public function getNoteTitle(): ?string
    {
        return $this->noteTitle;
    }

    public function setNoteTitle(?string $noteTitle): self
    {
        $this->noteTitle = $noteTitle;

        return $this;
    }

    public function getNoteSummary(): ?string
    {
        return $this->noteSummary;
    }

    public function setNoteSummary(?string $noteSummary): self
    {
        $this->noteSummary = $noteSummary;

        return $this;
    }

    public function getLogoImage(): ?string
    {
        return $this->logoImage;
    }

    public function setLogoImage(?string $logoImage): self
    {
        $this->logoImage = $logoImage;

        return $this;
    }

    public function getImagePrefix(): ?string
    {
        return $this->imagePrefix;
    }

    public function setImagePrefix(?string $imagePrefix): self
    {
        $this->imagePrefix = $imagePrefix;

        return $this;
    }

    public function getLogoImageURL(): ?string
    {
        if (is_null($this->logoImage)) {
            return null;
        }

        if (!is_null($this->imagePrefix)) {
            return $this->imagePrefix.$this->logoImage;
        }

        return $this->logoImage;
    }

    public function getLogoWidth(): ?int
    {
        return $this->logoWidth;
    }

    public function setLogoWidth(?int $logoWidth): self
    {
        $this->logoWidth = $logoWidth;

        return $this;
    }

    public function getBoeThreshold(): float
    {
        return $this->boeThreshold;
    }

    public function setBoeThreshold(float $boeThreshold): self
    {
        $this->boeThreshold = $boeThreshold;

        return $this;
    }

    public function getBoeAmount(): float
    {
        return $this->boeAmount;
    }

    public function setBoeAmount(float $boeAmount): self
    {
        $this->boeAmount = $boeAmount;

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

    /**
     * @return ArrayCollection|Collection
     */
    public function getZones()
    {
        return $this->zones;
    }

    /**
     * @param ArrayCollection|Collection $zones
     */
    public function setZones($zones): self
    {
        $this->zones = $zones;

        return $this;
    }
}
