<?php

namespace App\Entity;

use App\Repository\UserTokenEntityRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserTokenEntityRepository::class)
 * @ORM\Table(name="sendit_user_tokens")
 * @UniqueEntity("token")
 * @ExclusionPolicy("all")
 */
class UserTokenEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserEntity", inversedBy="tokens", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private UserEntity $user;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private string $token;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $expiryDate;

    public function getId(): ?string
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

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

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

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?DateTime $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }
}
