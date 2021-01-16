<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VoterRepository")
 * @ORM\Table(name="voters",uniqueConstraints={@ORM\UniqueConstraint(name="login_idx", columns={"login"})})
 */
class Voter implements JsonSerializable {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $login;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $lastname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $promotion;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $ballot;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $signature;

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
     * Set firstname
     *
     * @param string $firstname
     *
     * @return Voter
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return Voter
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set group
     *
     * @param string $promotion
     *
     * @return Voter
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * Get group
     *
     * @return string
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * Set ballot
     *
     * @param string $ballot
     *
     * @return Voter
     */
    public function setBallot($ballot)
    {
        $this->ballot = $ballot;

        return $this;
    }

    /**
     * Get ballot
     *
     * @return string
     */
    public function getBallot()
    {
        return $this->ballot;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return Voter
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }


    public function hasVoted()
    {
        return $this->ballot !== null;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Voter
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set signature
     *
     * @param string $signature
     *
     * @return Voter
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    public function jsonSerialize()
    {
        return [
            'id'        => $this->id,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
            'login'     => $this->login,
            'email'     => $this->email,
            'hasVoted'  => $this->ballot !== null,
            'ballot'    => $this->ballot,
        ];
    }

    public function __toString(): string
    {
        return '#' . $this->id . ' ' . $this->firstname . ' ' . $this->lastname . ' (' . $this->email . ')';
    }
}
