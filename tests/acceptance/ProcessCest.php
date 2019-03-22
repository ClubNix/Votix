<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;

class ProcessCest
{
    public function testSeeVoteCounting(AcceptanceTester $I): void
    {
        $I->amOnPage('/no/stress');
        $I->see('Procédure de déchiffrement');
        $I->see('Clef Votix');
        $I->see('Mot de passe de déchiffrement');
        $I->seeElement('input[type=submit][value=Déchiffrer]');
    }
}
