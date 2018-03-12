<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

class AdminCest
{
    public function testIndex(\AcceptanceTester $I)
    {
        $I->amOnPage('/admin/voters-list');
        $I->see("Votix");
    }
}
