<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sendit_zone_prices",uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *     name="price_unique",
 *     columns={"zone_id", "price_type","price_for","weight"})})
 * @ExclusionPolicy("all")
 */
class ZonePriceEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ZoneEntity", inversedBy="prices", fetch="EAGER")
     * @ORM\JoinColumn(name="zone_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Expose
     */
    private ZoneEntity $zone;

    /**
     * @ORM\Column(type="string", length=50, name="price_type")
     * @Expose
     */
    private string $type = 'export';

    /**
     * @ORM\Column(type="string", length=50, name="price_for")
     * @Expose
     */
    private string $for = 'package';

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $weight = 0.0;

    /**
     * @ORM\Column(type="float")
     * @Expose
     */
    private float $price = 0.0;

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

    public function getZone(): ZoneEntity
    {
        return $this->zone;
    }

    public function setZone(ZoneEntity $zone): self
    {
        $this->zone = $zone;

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

    public function getFor(): string
    {
        return $this->for;
    }

    public function setFor(string $for): self
    {
        $this->for = $for;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

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
