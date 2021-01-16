<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Unit\Service;

use App\Service\EncryptionService;
use App\Service\EncryptionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class EncryptionServiceTest
 * @package Tests\AppBundle\Entity
 */
class EncryptionServiceTest extends WebTestCase
{
    private $goodKeysDirectory = __DIR__ . '/../../_data/fixtures';
    private $secretKey         = 'testsecret';
    private $fakevote          = '42';

    public function testIsArmedWithArmedFilesystem(): EncryptionService
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $this->assertTrue($encryptionService->isArmed());

        return $encryptionService;
    }

    public function testIsArmedWithDisarmedFilesystem(): void
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getDisarmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $this->assertFalse($encryptionService->isArmed());
    }

    public function testVerifyKeyWithInvalidKeyOrPassphrase(): void
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);
        [$success] = $encryptionService->verifyKey('tapz');
        $this->assertFalse($success);

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, 'bad key', $filesystem);
        [$success] = $encryptionService->verifyKey($this->getPrivateKeyFixture());
        $this->assertFalse($success);
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     *
     * @param EncryptionServiceInterface $encryptionService
     */
    public function testVerifyKeyWithValidKeyAndPassphrase(EncryptionServiceInterface $encryptionService): void
    {
        [$success] = $encryptionService->verifyKey($this->getPrivateKeyFixture());
        $this->assertTrue($success);
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     *
     * @param EncryptionServiceInterface $encryptionService
     *
     * @return string
     */
    public function testEncryption(EncryptionServiceInterface $encryptionService): string
    {
        $encrypted = $encryptionService->encryptVote($this->fakevote);

        $this->assertNotNull($encrypted);
        $this->assertNotEquals($this->fakevote, $encrypted);

        return $encrypted;
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     * @depends testEncryption
     *
     * @param EncryptionServiceInterface $encryptionService
     * @param string $encrypted
     */
    public function testDecryption(EncryptionServiceInterface $encryptionService, string $encrypted): void
    {
        $decrypted = $encryptionService->decryptVote($encrypted, $this->getPrivateKeyFixture());

        $this->assertNotNull($decrypted);
        $this->assertNotEquals($encrypted, $decrypted);

        $this->assertEquals($this->fakevote, $decrypted);
    }

    public function testGenerateKeys(): void
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $filesystem
            ->expects($this->exactly(2))
            ->method('dumpFile');

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $encryptionService->generateKeys();
    }

    private function getEventDispatcherMock(): EventDispatcherInterface
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->getMock();

        return $eventDispatcher;
    }

    private function getPrivateKeyFixture(): string
    {
        return file_get_contents($this->goodKeysDirectory . '/votix_secret_key.txt');
    }

    private function getArmedFilesystemMock(): Filesystem
    {
        $filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->getMock();

        $filesystem->method('exists')
            ->with($this->goodKeysDirectory . '/public_key.pub')
            ->willReturn(true);

        /** @var Filesystem $filesystem */
        return $filesystem;
    }

    private function getDisarmedFilesystemMock()
    {
        $filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->getMock();

        $filesystem->method('exists')
            ->with($this->goodKeysDirectory . '/public_key.pub')
            ->willReturn(false);

        /** @var Filesystem $filesystem */
        return $filesystem;
    }
}