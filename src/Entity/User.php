<?php

namespace App\Entity;

use App\Entity\Common\AccountRelatedEntityInterface;
use App\Entity\Common\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="user", indexes={@ORM\Index(name="accountid_time", columns={"accountid", "time"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\EntityListeners({"Runalyze\Bundle\CoreBundle\EntityListener\UserListener"})
 */
class User implements IdentifiableEntityInterface, AccountRelatedEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="time", type="integer", precision=11, nullable=false, options={"unsigned":true})
     */
    private $time;

    /**
     * @var float|null [kg]
     *
     * @ORM\Column(name="weight", type="casted_decimal_2", precision=5, scale=2, nullable=true, options={"unsigned":true})
     */
    private $weight;

    /**
     * @var int|null [bpm]
     *
     * @ORM\Column(name="pulse_rest", type="tinyint", nullable=true, options={"unsigned":true})
     */
    private $pulseRest;

    /**
     * @var int|null [bpm]
     *
     * @ORM\Column(name="pulse_max", type="tinyint", nullable=true, options={"unsigned":true})
     */
    private $pulseMax;

    /**
     * @var float|null [%]
     * @Assert\Range(
     *     min = 0,
     *     max = 100)
     * @ORM\Column(name="fat", type="casted_decimal_1", precision=3, scale=1, nullable=true)
     */
    private $fat;

    /**
     * @var float|null [%]
     * @Assert\Range(
     *     min = 0,
     *     max = 100)
     * @ORM\Column(name="water", type="casted_decimal_1", precision=3, scale=1, nullable=true)
     */
    private $water;

    /**
     * @var float|null [%]
     *
     * @Assert\Range(
     *     min = 0,
     *     max = 100)
     * @ORM\Column(name="muscles", type="casted_decimal_1", precision=3, scale=1, nullable=true)
     */
    private $muscles;

    /**
     * @var int|null [min]
     *
     * @ORM\Column(name="sleep_duration", type="smallint", precision=3, nullable=true, options={"unsigned":true})
     */
    private $sleepDuration;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", length=65535, nullable=true)
     */
    private $notes;

    /**
     * @var \App\Entity\Account
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    public function __clone() {
        $this->id = null;
        $this->notes = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $time
     *
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCurrentTimestamp()
    {
        $this->time = time();

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param float|null $weight [kg]
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return float|null [kg]
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int|null $pulseRest [bpm]
     *
     * @return $this
     */
    public function setPulseRest($pulseRest)
    {
        $this->pulseRest = $pulseRest;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getPulseRest()
    {
        return $this->pulseRest;
    }

    /**
     * @param int|null $pulseMax [bpm]
     *
     * @return $this
     */
    public function setPulseMax($pulseMax)
    {
        $this->pulseMax = $pulseMax;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getPulseMax()
    {
        return $this->pulseMax;
    }

    /**
     * @param float|null $fat [%]
     *
     * @return $this
     */
    public function setFat($fat)
    {
        $this->fat = $fat;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getFat()
    {
        return $this->fat;
    }

    /**
     * @param float|null $water [%]
     *
     * @return $this
     */
    public function setWater($water)
    {
        $this->water = $water;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getWater()
    {
        return $this->water;
    }

    /**
     * @param float|null $muscles [%]
     *
     * @return $this
     */
    public function setMuscles($muscles)
    {
        $this->muscles = $muscles;

        return $this;
    }

    /**
     * @return float|null [%]
     */
    public function getMuscles()
    {
        return $this->muscles;
    }

    /**
     * @param int|null $sleepDuration [min]
     *
     * @return $this
     */
    public function setSleepDuration($sleepDuration)
    {
        $this->sleepDuration = $sleepDuration;

        return $this;
    }

    /**
     * @return int|null [min]
     */
    public function getSleepDuration()
    {
        return $this->sleepDuration;
    }

    /**
     * @param string|null $notes
     *
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
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
     * @return User
     */
    public function cloneObjectForForm()
    {
        return (new User())
            ->setAccount($this->account)
            ->setCurrentTimestamp()
            ->setPulseMax($this->pulseMax)
            ->setPulseRest($this->pulseRest)
            ->setWeight($this->weight)
            ->setMuscles($this->muscles)
            ->setFat($this->fat)
            ->setWater($this->water);
    }
}
