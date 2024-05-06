<?php

namespace App\Entity;

use App\Repository\UserAddressEntityRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserAddressEntityRepository::class)
 * @ORM\Table(name="sendit_user_addresses")
 * @UniqueEntity("token")
 * @ExclusionPolicy("all")
 */
class UserAddressEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Expose
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserEntity", inversedBy="addresses", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private UserEntity $user;

    /**
     * @ORM\Column(type="string", nullable=false, name="address_name")
     * @Expose
     */
    private string $name;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private string $contactName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private ?string $contactMobile;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private ?string $contactEmail;

    /**
     * @ORM\Column(type="string", nullable=false, name="address_type")
     * @Expose
     */
    private string $type;

    /**
     * @ORM\Column(type="text", nullable=false, name="address_primary")
     * @Expose
     */
    private string $primary;

    /**
     * @ORM\Column(type="text", nullable=true, name="address_secondary")
     * @Expose
     */
    private ?string $secondary;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Expose
     */
    private ?string $landmark;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private string $cityName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private ?string $zipCode;

    /**
     * @ORM\Column(type="string", nullable=true,  name="address_state")
     * @Expose
     */
    private ?string $state;

    /**
     * @ORM\Column(type="string", length=5, nullable=false, options={"default":"AE"})
     * @Expose
     */
    private string $countryCode = 'AE';

    /**
     * @Expose
     */
    private ?string $countryName = null;

    /**
     * @Expose
     */
    private ?string $countryDialCode = null;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): self
    {
        $this->user = $user;

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

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactMobile(): ?string
    {
        return $this->contactMobile;
    }

    public function setContactMobile(?string $contactMobile): self
    {
        $this->contactMobile = $contactMobile;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
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

    public function getPrimary(): string
    {
        return $this->primary;
    }

    public function setPrimary(string $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function getSecondary(): ?string
    {
        return $this->secondary;
    }

    public function setSecondary(?string $secondary): self
    {
        $this->secondary = $secondary;

        return $this;
    }

    public function getLandmark(): ?string
    {
        return $this->landmark;
    }

    public function setLandmark(?string $landmark): self
    {
        $this->landmark = $landmark;

        return $this;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function setCityName(string $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getCountryDialCode(): ?string
    {
        return $this->countryDialCode;
    }

    public function setCountryDialCode(?string $countryDialCode): void
    {
        $this->countryDialCode = $countryDialCode;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function setCountryName(?string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
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
}
