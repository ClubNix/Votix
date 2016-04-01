<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Candidate;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CandidateTest
 * @package Tests\AppBundle\Entity
 */
class CandidateTest extends WebTestCase
{
    public function testGettersAndSetters() {
        $candidate = new Candidate();

        $candidate->setEligible(false);
        $candidate->setName("test candidate");

        $this->assertFalse($candidate->getEligible());
        $this->assertEquals("test candidate", $candidate->getName());

        $candidate->setEligible(true);
        $candidate->setName("test2 candidate");

        $this->assertTrue($candidate->getEligible());
        $this->assertEquals("test2 candidate", $candidate->getName());
    }
}