<?php

namespace SpikeTeam\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use SpikeTeam\UserBundle\Entity\Spiker;
use SpikeTeam\ButtonBundle\Entity\ButtonPush;
use Doctrine\Common\Collections\Criteria;

/**
 * SpikerGroup
 *
 * @ORM\Table(name="spiker_group")
 * @ORM\Entity(repositoryClass="SpikeTeam\UserBundle\Entity\SpikerGroupRepository")
 */
class SpikerGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Spiker", mappedBy="group")
     */
    private $spikers;

    /**
     * @ORM\OneToMany(targetEntity="SpikeTeam\ButtonBundle\Entity\ButtonPush", mappedBy="group")
     */
    private $pushes;

    /**
     * @ORM\OneToOne(targetEntity="Spiker")
     */
    private $captain;

    /**
     * @ORM\Column(type="boolean", options={"default" = 1})
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="boolean", options={"default" = 1})
     */
    private $public = true;

    public function __construct()
    {
        $this->spikers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pushes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return SpikerGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add spikers
     *
     * @param Spiker $spikers
     *
     * @return SpikerGroup
     */
    public function addSpiker(Spiker $spikers)
    {
        $this->spikers[] = $spikers;

        return $this;
    }

    /**
     * Remove spikers
     *
     * @param Spikers $spikers
     */
    public function removeSpiker(Spiker $spikers)
    {
        $this->spikers->removeElement($spikers);
    }

    /**
     * Get spikers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpikers()
    {
        return $this->spikers;
    }

    /**
     * Add pushes
     *
     * @param ButtonPush $pushes
     *
     * @return SpikerGroup
     */
    public function addPush(ButtonPush $pushes)
    {
        $this->pushes[] = $pushes;

        return $this;
    }

    /**
     * Remove pushes
     *
     * @param ButtonPush $pushes
     */
    public function removePush(ButtonPush $pushes)
    {
        $this->pushes->removeElement($pushes);
    }

    /**
     * Get pushes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPushes()
    {
        return $this->pushes;
    }

    /**
     * Get pushes within last 24 hours
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentPushesCount()
    {
        $date = new \DateTime();
        $date->modify('-24 hours');
        $criteria = Criteria::create()->where(Criteria::expr()->gt("pushTime", $date));
        $recentPushes = $this->pushes->matching($criteria);

        return $recentPushes->count();
    }

    public function __toString()
    {
        return strval($this->id);
    }

    /**
     * Set enabled
     *
     * @param bool $enabled
     * @return SpikerGroup
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set if group is public
     *
     * @param bool $enabled
     * @return SpikerGroup
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get if group is public
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set captain
     *
     * @param Spiker $captain
     * @return SpikerGroup
     */
    public function setCaptain(Spiker $captain = null)
    {
        $this->captain = $captain;

        return $this;
    }

    /**
     * Get captain
     *
     * @return Spiker
     */
    public function getCaptain()
    {
        return $this->captain;
    }
}
