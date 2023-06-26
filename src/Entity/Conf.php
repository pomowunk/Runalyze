<?php

namespace App\Entity;

use App\Entity\Common\AccountRelatedEntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conf
 *
 * @ORM\Table(name="conf", indexes={@ORM\Index(name="accountid", columns={"accountid"})})
 * @ORM\Entity(repositoryClass="App\Repository\ConfRepository")
 */
class Conf implements AccountRelatedEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=32, nullable=false)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="`key`", type="string", length=100, nullable=false)
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=false)
     */
    private $value;

    /**
     * @var \App\Entity\Account
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
}
