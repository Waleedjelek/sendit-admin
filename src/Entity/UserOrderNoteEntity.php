<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_user_order_notes")
 * @ExclusionPolicy("all")
 */
class UserOrderNoteEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserEntity", inversedBy="orders", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private UserEntity $user;

    /**
     * @ORM\ManyToOne(targetEntity="UserOrderEntity", inversedBy="notes", fetch="EAGER")
     * @ORM\JoinColumn(name="user_order_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private UserOrderEntity $order;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $description;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $oldStatus;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $newStatus;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $createdDate;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): self
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): self
    {
        $this->newStatus = $newStatus;

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
}
