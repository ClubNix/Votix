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
 * Class ScreenApiControllerTest
 * @package Tests\AppBundle\Controller
 */
class ScreenApiControllerTest extends WebTestCase
{
    public function testLiveApi()
    {
        $client = static::createClient();

        $client->request('GET', '/live.php');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));
        $this->assertNotEmpty($client->getResponse()->getContent());

        $responseContent = $client->getResponse()->getContent();

        $this->assertJson($responseContent);

        $response = json_decode($responseContent, $assoc = true);

        $this->assertArrayHasKey('status', $response);
        $this->assertInternalType('string', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertInternalType('string', $response['message']);
        $this->assertArrayHasKey('total', $response);
        $this->assertInternalType('int', $response['total']);
        $this->assertArrayHasKey('ratio', $response);

        for($i = 1; $i < 10; $i++) {
            $this->assertArrayHasKey("progress_${i}_label", $response);
            $this->assertInternalType('string', $response["progress_${i}_label"]);

            $this->assertArrayHasKey("progress_${i}_ratio", $response);
        }
    }
}

