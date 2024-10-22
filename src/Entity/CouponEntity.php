<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_coupon")
 * @ExclusionPolicy("all")
 */
class CouponEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    
    /**
     * @ORM\Column(type="integer", name="discount")
     */
    private int $discount = 0;

    /**
     * @ORM\Column(type="string", length=255, name="coupon")
     * @Expose
     */
  
     private string $coupon;
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Expose
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $createdDate;
  

    /**
     * @param string $id
     */
    public function __construct()
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCoupon(): string
    {
        return $this->coupon;
    }

    public function setCoupon(string $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }

    
        public function getDiscount():?int
        {
            return $this->discount;
        }
    public function setDiscount(int $discount): self
    {
        $this->discount = $discount;

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

    
    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

}
