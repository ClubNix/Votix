<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;

class NavigationCest
{
    public function testIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Élections du BDE'); //TODO translate

        $I->see('Homepage');
        $I->see('Hall of Fame');
        $I->see('F.A.Q.');

        $I->see('Vote status');
    }

    public function testLinks(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Homepage');
    }

    public function testFaq(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('F.A.Q.');
        $I->see('Qui va gagner ?'); //TODO translate
    }

    public function testHallOfFame(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Hall of Fame');
        $I->see('Candidate');
        $I->see('Vote count');
        $I->see('Percentage');
    }
}
