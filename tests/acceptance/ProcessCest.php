<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

class ProcessCest
{
    public function testSeeVoteCounting(\AcceptanceTester $I)
    {
        $I->amOnPage('/no/stress');
        $I->see('Procédure de déchiffrement');
        $I->see('Clef Votix');
        $I->see('Mot de passe de déchiffrement');
        $I->see('Déchiffrer');
    }

}
