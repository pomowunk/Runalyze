<?php

namespace App\Entity;

use App\Entity\Common\AccountRelatedEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * EquipmentType
 *
 * @ORM\Table(name="equipment_type")
 * @ORM\Entity(repositoryClass="App\Repository\EquipmentTypeRepository")
 */
class EquipmentType implements AccountRelatedEntityInterface
{
    /** @var int only one equipment object can be used at once */
    const CHOICE_SINGLE = 0;

    /** @var int multiple equipment objects can be used at once */
    const CHOICE_MULTIPLE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=10, nullable=false, options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name = '';

    /**
     * @var int see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     *
     * @ORM\Column(name="input", type="tinyint", options={"unsigned":true})
     */
    private $input = 0;

    /**
     * @var null|int [km]
     *
     * @ORM\Column(name="max_km", type="integer", nullable=true, options={"unsigned":true})
     */
    private $maxKm = null;

    /**
     * @var null|int [s]
     *
     * @ORM\Column(name="max_time", type="integer", nullable=true, options={"unsigned":true})
     */
    private $maxTime = null;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Account", inversedBy="equipmentTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Sport", inversedBy="equipmentType")
     * @ORM\JoinTable(name="equipment_sport",
     *   joinColumns={
     *     @ORM\JoinColumn(name="equipment_typeid", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="sportid", referencedColumnName="id")
     *   }
     * )
     */
    private $sport;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Equipment", mappedBy="type", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $equipment;

    public function __construct()
    {
        $this->sport = new ArrayCollection();
        $this->equipment = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param int $input see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     *
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return int see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return bool
     */
    public function allowsMultipleValues()
    {
        return self::CHOICE_MULTIPLE === $this->input;
    }

    /**
     * @param null|int $maxKm [km]
     *
     * @return $this
     */
    public function setMaxKm($maxKm)
    {
        $this->maxKm = $maxKm;

        return $this;
    }

    /**
     * @return null|int [km]
     */
    public function getMaxKm()
    {
        return $this->maxKm;
    }

    /**
     * @return bool
     */
    public function hasMaximalDistance()
    {
        return null !== $this->maxKm && $this->maxKm > 0;
    }

    /**
     * @param null|int $maxTime [s]
     *
     * @return $this
     */
    public function setMaxTime($maxTime)
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * @return bool
     */
    public function hasMaximalDuration()
    {
        return null !== $this->maxTime && $this->maxTime > 0;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    /**
     * @param Sport $sport
     *
     * @return $this
     */
    public function addSport(Sport $sport)
    {
        $this->sport[] = $sport;

        return $this;
    }

    /**
     * @param Sport $sport
     */
    public function removeSport(Sport $sport)
    {
        $this->sport->removeElement($sport);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquipment()
    {
        return $this->equipment;
    }
}
