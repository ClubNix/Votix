<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Voter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class VoterTest
 * @package Tests\AppBundle\Entity
 */
class VoterTest extends WebTestCase
{
    public function testGettersAndSetters() {
        $candidate = new Voter();

        $candidate
            ->setLogin('login')
            ->setEmail('test@mail.tld')
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setPromotion('promotion')
        ;

        $this->assertEquals('login', $candidate->getLogin());
        $this->assertEquals('test@mail.tld', $candidate->getEmail());
        $this->assertEquals('firstname', $candidate->getFirstname());
        $this->assertEquals('lastname', $candidate->getLastname());
        $this->assertEquals('promotion', $candidate->getPromotion());
    }

    public function testHasVoted() {
        $candidate = new Voter();

        $this->assertFalse($candidate->hasVoted());

        $candidate->setBallot('fake vote');

        $this->assertTrue($candidate->hasVoted());
    }
}