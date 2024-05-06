<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_user_transactions")
 * @UniqueEntity("transId")
 * @ExclusionPolicy("all")
 */
class UserTransactionEntity
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
     * @ORM\ManyToOne(targetEntity="UserOrderEntity", inversedBy="transactions", fetch="EAGER")
     * @ORM\JoinColumn(name="user_order_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private UserOrderEntity $order;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private string $transId;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private int $runIndex = 0;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private ?string $referenceCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $redirectURL = '';

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $responseValues;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $postBackValues;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $paidAmount = 0.0;

    /**
     * @ORM\Column(type="float", options={"default":0.0})
     */
    private float $refundAmount = 0.0;

    /**
     * @ORM\Column(type="string", nullable=true, options={"default":"Pending"})
     */
    private ?string $paymentStatus = 'Pending';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $paidCurrency;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?string $statusCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $statusText;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $transactionStatus;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $transactionMessage;

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

    public function getOrder(): UserOrderEntity
    {
        return $this->order;
    }

    public function setOrder(UserOrderEntity $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getTransId(): string
    {
        return $this->transId;
    }

    public function setTransId(string $transId): void
    {
        $this->transId = $transId;
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

    public function getReferenceCode(): ?string
    {
        return $this->referenceCode;
    }

    public function setReferenceCode(?string $referenceCode): self
    {
        $this->referenceCode = $referenceCode;

        return $this;
    }

    public function getRedirectURL(): ?string
    {
        return $this->redirectURL;
    }

    public function setRedirectURL(?string $redirectURL): self
    {
        $this->redirectURL = $redirectURL;

        return $this;
    }

    public function getResponseValues(): ?array
    {
        return $this->responseValues;
    }

    public function setResponseValues(?array $responseValues): self
    {
        $this->responseValues = $responseValues;

        return $this;
    }

    public function getPostBackValues(): ?array
    {
        return $this->postBackValues;
    }

    public function setPostBackValues(?array $postBackValues): self
    {
        $this->postBackValues = $postBackValues;

        return $this;
    }

    public function getPaidAmount(): float
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(float $paidAmount): self
    {
        $this->paidAmount = $paidAmount;

        return $this;
    }

    public function getRefundAmount(): float
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(float $refundAmount): self
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaidCurrency(): ?string
    {
        return $this->paidCurrency;
    }

    public function setPaidCurrency(?string $paidCurrency): self
    {
        $this->paidCurrency = $paidCurrency;

        return $this;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function setStatusCode(?string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusText(): ?string
    {
        return $this->statusText;
    }

    public function setStatusText(?string $statusText): self
    {
        $this->statusText = $statusText;

        return $this;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->transactionStatus;
    }

    public function setTransactionStatus(?string $transactionStatus): void
    {
        $this->transactionStatus = $transactionStatus;
    }

    public function getTransactionMessage(): ?string
    {
        return $this->transactionMessage;
    }

    public function setTransactionMessage(?string $transactionMessage): self
    {
        $this->transactionMessage = $transactionMessage;

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
