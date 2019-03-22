<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;

class AdminCest
{
    public function testIndex(AcceptanceTester $I) :void
    {
        $I->amOnPage('/admin/voters-list');
        $I->see('Votix');
    }
}
