<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;

class DemoCest
{
    public function testDemoIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo');
        $I->see('Démos disponibles');
    }

    public function testDemoArm(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/arm');
        $I->see('Génération de la clef');
        $I->see('Protection de la clef');
    }

    public function testDemoBadTiming(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/vote-bad-timing');
        $I->see('Il est trop tôt ou trop tard pour voter.');
    }

    public function testDemoAlreadyVoted(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/already-voted');
        $I->see('tu as déjà voté !');
    }

    public function testDemoThankYou(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/thank-you');
        $I->see('A voté !');
    }

    public function testDemoVote(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/vote');
        $I->see('Candidate A');
    }

    public function testDemoVoteCounting(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/vote-counting');
        $I->see('Procédure de déchiffrement');
    }

    public function testDemoResults(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/demo/results');
        $I->see('Les résultats');
    }
}
