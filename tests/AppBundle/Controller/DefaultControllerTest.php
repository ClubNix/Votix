<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class DefaultControllerTest
 * @package Tests\AppBundle\Controller
 */
class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->filter('.website-title')->count());

        // 3 links in menu
        $this->assertEquals(3, $crawler->filter('nav ul li')->count());
    }

    public function testFaq() {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/faq');

        $this->assertTrue($client->getResponse()->isSuccessful());

        // 6 paragraphs
        $this->assertEquals(6, $crawler->filter('h4')->count());
    }

    public function testHallOfFame() {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/hall-of-fame');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertEquals(1, $crawler->filter('table')->count());
    }
}
