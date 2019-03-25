<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Voter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class VoterTest
 */
class VoterTest extends WebTestCase
{
    public function testGettersAndSetters(): void
    {
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

    public function testHasVoted(): void
    {
        $candidate = new Voter();

        $this->assertFalse($candidate->hasVoted());

        $candidate->setBallot('fake vote');

        $this->assertTrue($candidate->hasVoted());
    }
}