<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\DataFixtures;

use App\Entity\Candidate;
use App\Entity\Voter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $candidate1 = new Candidate();
        $candidate1->setName('candidate1');
        $candidate1->setEligible(true);
        $candidate2 = new Candidate();
        $candidate2->setName('candidate2');
        $candidate2->setEligible(true);
        $candidate3 = new Candidate();
        $candidate3->setName('candidate3 (non éligible)');
        $candidate3->setEligible(false);
        $candidate4 = new Candidate();
        $candidate4->setName('blanc');
        $candidate4->setEligible(true);
        $manager->persist($candidate1);
        $manager->persist($candidate2);
        $manager->persist($candidate3);
        $manager->persist($candidate4);

        $voter1 = new Voter();
        $voter1->setEmail('plop@edu.fr');
        $voter1->setFirstname('Voter1');
        $voter1->setLastname('Voter2');
        $voter1->setLogin('l');
        $voter1->setPromotion('18_E4FR');
        $manager->persist($voter1);

        $manager->flush();
    }
}
