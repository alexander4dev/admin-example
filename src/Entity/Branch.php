<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="branch")
 */
class Branch extends AbstractWorkingPlace
{
    use Traits\Timestampable;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(
     *   type="integer",
     *   options={
     *     "unsigned": true,
     *   },
     * )
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=false,
     * )
     *
     * @var string|null
     */
    protected $name;

    /**
     * @ORM\OneToOne(
     *   targetEntity="Warehouse",
     *   mappedBy="branch",
     * )
     *
     * @var Warehouse|null
     */
    protected $warehouse;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Sector",
     *   mappedBy="branch",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $sectors;

    public function __construct()
    {
        $this->sectors = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    /**
     * @param Warehouse $warehouse
     * @return self
     */
    public function setWarehouse(Warehouse $warehouse): self
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getSectors(): PersistentCollection
    {
        return $this->sectors;
    }

    /**
     * @param Sector $sector
     * @return self
     */
    public function addSector(Sector $sector): self
    {
        $this->sectors->add($sector);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s (#%d)', $this->name, $this->id);
    }
}
