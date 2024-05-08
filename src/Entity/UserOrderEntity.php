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
 * @ORM\Table(name="sendit_user_orders")
 * @UniqueEntity("orderId")
 * @ExclusionPolicy("all")
 */
class UserOrderEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Expose
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserEntity", inversedBy="orders", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    private UserEntity $user;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\CompanyEntity", fetch="EAGER")
     * @ORM\JoinColumn(name="selected_company_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    private CompanyEntity $selectedCompany;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserTransactionEntity", fetch="EAGER")
     * @ORM\JoinColumn(name="paid_transaction_id", referencedColumnName="id", nullable=true, onDelete="RESTRICT")
     */
    private ?UserTransactionEntity $paidTransaction = null;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private string $orderId;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private int $runIndex = 0;

    /**
     * @ORM\Column(type="string", length=10, name="order_type", options={"default":"int"})
     */
    private string $type = 'int';

    /**
     * @ORM\Column(type="string", length=10, name="order_method", options={"default":"export"})
     */
    private string $method = 'export';


    /**
     * @ORM\Column(type="string", length=255,name="discounted")
     */
    private ?string $discounted = null;
    
    /**
     * @ORM\Column(type="string", length=255,name="coupon")
     */
    private ?string $coupon = null;




    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $collectionDate = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $collectionTime = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $collectionAddressId = null;

    /**
     * @ORM\Column(type="json")
     */
    private ?array $collectionAddress = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $destinationAddressId = null;

    /**
     * @ORM\Column(type="json")
     */
    private ?array $destinationAddress = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $packageInfo = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $priceInfo = null;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private ?string $trackingCode = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $trackingInfo = null;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $boeAmount = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $totalValue = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $totalPrice = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $totalWeight = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $totalVolumeWeight = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $finalWeight = 0.0;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Expose
     */
    private bool $contactForInsurance = false;

    /**
     * @ORM\Column(type="string", options={"default":"Draft"})
     */
    private string $status = 'Draft';

    /**
     * @ORM\Column(type="string", options={"default":"Pending"})
     */
    private string $paymentStatus = 'Pending';

    /**
     * @ORM\Column(type="string", options={"default":""})
     */
    private string $successRedirectURL = '';

    /**
     * @ORM\Column(type="string", options={"default":""})
     */
    private string $failureRedirectURL = '';

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $modifiedDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $trackingAddedDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $trackingUpdatedDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $trackingFetchedDate = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserTransactionEntity", mappedBy="order", fetch="LAZY")
     * @ORM\OrderBy({"createdDate" = "DESC"})
     */
    private Collection $transactions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserOrderNoteEntity", mappedBy="order", fetch="LAZY")
     * @ORM\OrderBy({"createdDate" = "DESC"})
     */
    private Collection $notes;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

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

    public function getSelectedCompany(): CompanyEntity
    {
        return $this->selectedCompany;
    }

    public function setSelectedCompany(CompanyEntity $selectedCompany): self
    {
        $this->selectedCompany = $selectedCompany;

        return $this;
    }

    public function getPaidTransaction(): ?UserTransactionEntity
    {
        return $this->paidTransaction;
    }

    public function setPaidTransaction(?UserTransactionEntity $paidTransaction): self
    {
        $this->paidTransaction = $paidTransaction;

        return $this;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getCollectionDate(): ?DateTime
    {
        return $this->collectionDate;
    }

    public function setCollectionDate(?DateTime $collectionDate): self
    {
        $this->collectionDate = $collectionDate;

        return $this;
    }

    public function getCollectionTime(): ?string
    {
        return $this->collectionTime;
    }

    public function setCollectionTime(?string $collectionTime): self
    {
        $this->collectionTime = $collectionTime;

        return $this;
    }

    public function getCollectionAddressId(): ?string
    {
        return $this->collectionAddressId;
    }

    public function setCollectionAddressId(?string $collectionAddressId): self
    {
        $this->collectionAddressId = $collectionAddressId;

        return $this;
    }

    public function getCollectionAddress(): ?array
    {
        return $this->collectionAddress;
    }

    public function setCollectionAddress(?array $collectionAddress): self
    {
        $this->collectionAddress = $collectionAddress;

        return $this;
    }

    public function getDestinationAddressId(): ?string
    {
        return $this->destinationAddressId;
    }

    public function setDestinationAddressId(?string $destinationAddressId): self
    {
        $this->destinationAddressId = $destinationAddressId;

        return $this;
    }

    public function getDestinationAddress(): ?array
    {
        return $this->destinationAddress;
    }

    public function setDestinationAddress(?array $destinationAddress): self
    {
        $this->destinationAddress = $destinationAddress;

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

    public function getPriceInfo(): ?array
    {
        return $this->priceInfo;
    }

    public function setPriceInfo(?array $priceInfo): self
    {
        $this->priceInfo = $priceInfo;

        return $this;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public function getTrackingInfo(): ?array
    {
        return $this->trackingInfo;
    }

    public function setTrackingInfo(?array $trackingInfo): self
    {
        $this->trackingInfo = $trackingInfo;

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

    public function getTotalValue(): float
    {
        return $this->totalValue;
    }

    public function setTotalValue(float $totalValue): self
    {
        $this->totalValue = $totalValue;

        return $this;
    }

    public function getCouponCode(): ?string
    {
        return $this->coupon;
    }

    public function setCouponCode(string $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }
    public function getDiscounted(): ?string
    {
        return $this->discounted;
    }

    public function setDiscounted(string $discounted): self
    {
        $this->discounted = $discounted;

        return $this;
    }
    
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalWeight(): float
    {
        return $this->totalWeight;
    }

    public function setTotalWeight(float $totalWeight): self
    {
        $this->totalWeight = $totalWeight;

        return $this;
    }

    public function getTotalVolumeWeight(): float
    {
        return $this->totalVolumeWeight;
    }

    public function setTotalVolumeWeight(float $totalVolumeWeight): self
    {
        $this->totalVolumeWeight = $totalVolumeWeight;

        return $this;
    }

    public function getFinalWeight(): float
    {
        return $this->finalWeight;
    }

    public function setFinalWeight(float $finalWeight): self
    {
        $this->finalWeight = $finalWeight;

        return $this;
    }

    public function isContactForInsurance(): bool
    {
        return $this->contactForInsurance;
    }

    public function setContactForInsurance(bool $contactForInsurance): void
    {
        $this->contactForInsurance = $contactForInsurance;
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

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getSuccessRedirectURL(): string
    {
        return $this->successRedirectURL;
    }

    public function setSuccessRedirectURL(string $successRedirectURL): self
    {
        $this->successRedirectURL = $successRedirectURL;

        return $this;
    }

    public function getFailureRedirectURL(): string
    {
        return $this->failureRedirectURL;
    }

    public function setFailureRedirectURL(string $failureRedirectURL): self
    {
        $this->failureRedirectURL = $failureRedirectURL;

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

    public function getTrackingAddedDate(): ?DateTime
    {
        return $this->trackingAddedDate;
    }

    public function setTrackingAddedDate(?DateTime $trackingAddedDate): self
    {
        $this->trackingAddedDate = $trackingAddedDate;

        return $this;
    }

    public function getTrackingUpdatedDate(): ?DateTime
    {
        return $this->trackingUpdatedDate;
    }

    public function setTrackingUpdatedDate(?DateTime $trackingUpdatedDate): self
    {
        $this->trackingUpdatedDate = $trackingUpdatedDate;

        return $this;
    }

    public function getTrackingFetchedDate(): ?DateTime
    {
        return $this->trackingFetchedDate;
    }

    public function setTrackingFetchedDate(?DateTime $trackingFetchedDate): self
    {
        $this->trackingFetchedDate = $trackingFetchedDate;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param ArrayCollection|Collection $transactions
     */
    public function setTransactions($transactions): self
    {
        $this->transactions = $transactions;

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
