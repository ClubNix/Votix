<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;

class MonitoringCest
{
    public function testHealthz(AcceptanceTester $I): void
    {
        $I->amOnPage('/healthz');
        $I->see('OK');
    }
}
