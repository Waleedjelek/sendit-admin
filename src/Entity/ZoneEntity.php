<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_zones")
 * @ExclusionPolicy("all")
 */
class ZoneEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CompanyEntity", inversedBy="zones", fetch="EAGER")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    private CompanyEntity $company;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ZonePriceEntity", mappedBy="zone", fetch="LAZY")
     */
    private Collection $prices;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\CountryEntity")
     * @ORM\JoinTable(
     *     name="sendit_zone_countries",
     *     joinColumns={@ORM\JoinColumn(name="zone_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")}
     * )
     */
    private Collection $countries;

    /**
     * @ORM\Column(type="string", length=150)
     * @Expose
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=150)
     * @Expose
     */
    private string $name;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     * @Expose
     */
    private int $minDays = 0;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     * @Expose
     */
    private int $maxDays = 0;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $modifiedDate = null;

    public function __construct()
    {
        $this->countries = new ArrayCollection();
        $this->prices = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCompany(): CompanyEntity
    {
        return $this->company;
    }

    public function setCompany(CompanyEntity $company): self
    {
        $this->company = $company;

        return $this;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMinDays(): int
    {
        return $this->minDays;
    }

    public function setMinDays(int $minDays): self
    {
        $this->minDays = $minDays;

        return $this;
    }

    public function getMaxDays(): int
    {
        return $this->maxDays;
    }

    public function setMaxDays(int $maxDays): self
    {
        $this->maxDays = $maxDays;

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

    public function getCreatedDate(): ?DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getModifiedDate(): ?DateTime
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate(?DateTime $modifiedDate): self
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param ArrayCollection|Collection $countries
     */
    public function setCountries($countries): self
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @param ArrayCollection|Collection $prices
     */
    public function setPrices($prices): self
    {
        $this->prices = $prices;

        return $this;
    }
}
