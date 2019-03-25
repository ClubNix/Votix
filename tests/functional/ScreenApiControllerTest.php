<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Acceptance;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ScreenApiControllerTest
 */
class ScreenApiControllerTest extends WebTestCase
{
    public function testLiveApi(): void
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
        $this->assertIsString($response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertIsString($response['message']);
        $this->assertArrayHasKey('total', $response);
        $this->assertIsInt($response['total']);
        $this->assertArrayHasKey('ratio', $response);

        for($i = 1; $i < 10; $i++) {
            $this->assertArrayHasKey("progress_${i}_label", $response);
            $this->assertIsString($response["progress_${i}_label"]);

            $this->assertArrayHasKey("progress_${i}_ratio", $response);
        }
    }
}

