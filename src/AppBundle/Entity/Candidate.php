<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CandidateRepository")
 * @ORM\Table(name="candidates")
 */
class Candidate implements JsonSerializable {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $eligible;

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
     *
     * @return Candidate
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
     * Set eligible
     *
     * @param boolean $eligible
     *
     * @return Candidate
     */
    public function setEligible($eligible)
    {
        $this->eligible = $eligible;

        return $this;
    }

    /**
     * Get eligible
     *
     * @return boolean
     */
    public function getEligible()
    {
        return $this->eligible;
    }

    function jsonSerialize()
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'isEligible' => $this->eligible,
        ];
    }
}
