<?php

namespace App\Entity;

use App\Repository\UserEntityRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserEntityRepository::class)
 * @ORM\Table(name="sendit_users")
 * @UniqueEntity("email")
 * @ExclusionPolicy("all")
 */
class UserEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Expose
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Expose
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Expose
     */
    private bool $emailVerified = false;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $emailVerificationToken = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Expose
     */
    private ?string $firstName = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Expose
     */
    private ?string $lastName = null;

    /**
     * @ORM\Column(type="string", length=5, nullable=false, options={"default":"AE"})
     * @Expose
     */
    private string $countryCode = 'AE';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Expose
     */
    private ?string $mobileNumber = null;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Expose
     */
    private bool $mobileVerified = false;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $mobileVerificationCode = null;

    private ?string $profileImagePrefix = null;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Expose
     * @Accessor(getter="getProfileImageURL")
     */
    private ?string $profileImage = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private string $role;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $password = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $passwordResetToken = null;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Expose
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $lastLoginDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $modifiedDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $passwordResetTokenDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $emailVerificationTokenDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $mobileVerificationCodeDate = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserTokenEntity", mappedBy="user", fetch="LAZY")
     */
    private Collection $tokens;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserAddressEntity", mappedBy="user", fetch="LAZY")
     */
    private Collection $addresses;

    /**
     * @ORM\OneToMany(targetEntity="UserOrderEntity", mappedBy="user", fetch="LAZY")
     */
    private Collection $orders;

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): self
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): self
    {
        $this->emailVerificationToken = $emailVerificationToken;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

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

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): self
    {
        $this->mobileNumber = $mobileNumber;

        return $this;
    }

    public function isMobileVerified(): bool
    {
        return $this->mobileVerified;
    }

    public function setMobileVerified(bool $mobileVerified): self
    {
        $this->mobileVerified = $mobileVerified;

        return $this;
    }

    public function getMobileVerificationCode(): ?string
    {
        return $this->mobileVerificationCode;
    }

    public function setMobileVerificationCode(?string $mobileVerificationCode): self
    {
        $this->mobileVerificationCode = $mobileVerificationCode;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles[] = 'ROLE_USER';
        $roles[] = $this->role;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $filteredRoles = array_diff($roles, ['ROLE_USER']);
        $this->role = reset($filteredRoles) ?: 'ROLE_USER'; // Assign first role or default
        return $this;
    }

    public function getProfileImageURL(): ?string
    {
        if (is_null($this->profileImage)) {
            return null;
        }

        if (!is_null($this->profileImagePrefix)) {
            return $this->profileImagePrefix.$this->profileImage;
        }

        return $this->profileImage;
    }

    public function getProfileImagePrefix(): ?string
    {
        return $this->profileImagePrefix;
    }

    public function setProfileImagePrefix(?string $profileImagePrefix): self
    {
        $this->profileImagePrefix = $profileImagePrefix;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): self
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedDate(): DateTime
    {
        return $this->createdDate;
    }

    /**
     * @param mixed $createdDate
     */
    public function setCreatedDate(DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastLoginDate(): ?DateTime
    {
        return $this->lastLoginDate;
    }

    public function setLastLoginDate(DateTime $lastLoginDate): self
    {
        $this->lastLoginDate = $lastLoginDate;

        return $this;
    }

    public function getModifiedDate(): ?DateTime
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate(DateTime $modifiedDate): self
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    public function getPasswordResetTokenDate(): ?DateTime
    {
        return $this->passwordResetTokenDate;
    }

    public function setPasswordResetTokenDate(?DateTime $passwordResetTokenDate): self
    {
        $this->passwordResetTokenDate = $passwordResetTokenDate;

        return $this;
    }

    public function getEmailVerificationTokenDate(): ?DateTime
    {
        return $this->emailVerificationTokenDate;
    }

    public function setEmailVerificationTokenDate(?DateTime $emailVerificationTokenDate): self
    {
        $this->emailVerificationTokenDate = $emailVerificationTokenDate;

        return $this;
    }

    public function getMobileVerificationCodeDate(): ?DateTime
    {
        return $this->mobileVerificationCodeDate;
    }

    public function setMobileVerificationCodeDate(?DateTime $mobileVerificationCodeDate): self
    {
        $this->mobileVerificationCodeDate = $mobileVerificationCodeDate;

        return $this;
    }

    public function getTokens(): ArrayCollection
    {
        return $this->tokens;
    }

    public function setTokens(ArrayCollection $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param ArrayCollection|Collection $addresses
     */
    public function setAddresses($addresses): self
    {
        $this->addresses = $addresses;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param ArrayCollection|Collection $orders
     */
    public function setOrders($orders): void
    {
        $this->orders = $orders;
    }
}
