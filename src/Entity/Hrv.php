<?php

namespace App\Entity;

use App\Entity\Common\AccountRelatedEntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Hrv
 *
 * @ORM\Table(name="hrv")
 * @ORM\Entity(repositoryClass="App\Repository\HrvRepository")
 */
class Hrv implements AccountRelatedEntityInterface
{
    /**
     * @var array|null
     *
     * @ORM\Column(name="data", type="pipe_array", nullable=true)
     */
    private $data;

    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    /**
     * @var Training
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Training", inversedBy = "hrv")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="activityid", referencedColumnName="id")
     * })
     */
    private $activity;

    /**
     * @param array|null $data
     *
     * @return $this
     */
    public function setData($data = null)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
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
     * @param Training $activity
     *
     * @return $this
     */
    public function setActivity(Training $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Training
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
