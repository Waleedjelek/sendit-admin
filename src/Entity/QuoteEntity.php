<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_quotes")
 * @UniqueEntity("quoteId")
 * @ExclusionPolicy("all")
 */
class QuoteEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Expose
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CountryEntity", fetch="EAGER")
     * @ORM\JoinColumn(name="source_country_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    private CountryEntity $sourceCountry;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CountryEntity", fetch="EAGER")
     * @ORM\JoinColumn(name="destination_country_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    private CountryEntity $destinationCountry;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private string $quoteId;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private int $runIndex = 0;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private string $contactName;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private string $contactMobile;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private string $contactEmail;

    /**
     * @ORM\Column(type="string", nullable=true,  name="source_state")
     * @Expose
     */
    private ?string $sourceState;

    /**
     * @ORM\Column(type="string", nullable=true,  name="destination_state")
     * @Expose
     */
    private ?string $destinationState;

    /**
     * @ORM\Column(type="string", length=10, name="order_type", options={"default":"int"})
     */
    private string $type = 'int';

    /**
     * @ORM\Column(type="json")
     */
    private ?array $packageInfo;

    /**
     * @ORM\Column(type="string", options={"default":"Draft"})
     */
    private string $status = 'New';

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $modifiedDate = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteNoteEntity", mappedBy="quote", fetch="LAZY")
     * @ORM\OrderBy({"createdDate" = "DESC"})
     */
    private Collection $notes;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceCountry(): CountryEntity
    {
        return $this->sourceCountry;
    }

    public function setSourceCountry(CountryEntity $sourceCountry): self
    {
        $this->sourceCountry = $sourceCountry;

        return $this;
    }

    public function getDestinationCountry(): CountryEntity
    {
        return $this->destinationCountry;
    }

    public function setDestinationCountry(CountryEntity $destinationCountry): self
    {
        $this->destinationCountry = $destinationCountry;

        return $this;
    }

    public function getQuoteId(): string
    {
        return $this->quoteId;
    }

    public function setQuoteId(string $quoteId): self
    {
        $this->quoteId = $quoteId;

        return $this;
    }

    public function getRunIndex(): int
    {
        return $this->runIndex;
    }

    public function setRunIndex(int $runIndex): self
    {
        $this->runIndex = $runIndex;

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

    public function getContactMobile(): string
    {
        return $this->contactMobile;
    }

    public function setContactMobile(string $contactMobile): self
    {
        $this->contactMobile = $contactMobile;

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getSourceState(): ?string
    {
        return $this->sourceState;
    }

    public function setSourceState(?string $sourceState): void
    {
        $this->sourceState = $sourceState;
    }

    public function getDestinationState(): ?string
    {
        return $this->destinationState;
    }

    public function setDestinationState(?string $destinationState): self
    {
        $this->destinationState = $destinationState;

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

    public function getPackageInfo(): ?array
    {
        return $this->packageInfo;
    }

    public function setPackageInfo(?array $packageInfo): self
    {
        $this->packageInfo = $packageInfo;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param ArrayCollection|Collection $notes
     */
    public function setNotes($notes): void
    {
        $this->notes = $notes;
    }
}
