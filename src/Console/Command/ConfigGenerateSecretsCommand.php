<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConfigGenerateCommand
 */
class ConfigGenerateSecretsCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:config:generate-secrets')
            ->setDescription('Generate secrets')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dotEnvFilepath = __DIR__ . '/../../../.env.local';

        if (file_exists($dotEnvFilepath)) {
            $output->write("Config $dotEnvFilepath already exists. Exiting.");

            return -1;
        }

        // 512 bits secret
        $frameworkSecretEncoded = base64_encode(random_bytes(32));

        // 512 bits secret
        $secretEncoded = base64_encode(random_bytes(32));

        // 20 chars password for encrypting certificates
        $keySecretEncoded = base64_encode($this->generateHumanPassword(20));

        // 15 chars password to access the decryption page
        $resultPassword = $this->generateHumanPassword(15);
        $resultPasswordEncoded = base64_encode($resultPassword);

        $config = '';
        $config .= "APP_SECRET=$frameworkSecretEncoded" . PHP_EOL;
        $config .= "VOTIX_SECRET=$secretEncoded" . PHP_EOL;
        $config .= "VOTIX_KEY_SECRET=$keySecretEncoded" . PHP_EOL;
        $config .= "VOTIX_RESULT_PASSWORD=$resultPasswordEncoded" . PHP_EOL;

        if (file_put_contents($dotEnvFilepath, $config) === false) {
            $output->write("$dotEnvFilepath is not writeable.");

            return -1;
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Parameter', 'Value', 'Action needed'])
            ->setRows([
                ['app_secret', '[stored in .env.local]', 'Keep it secret !'],
                ['votix_secret', '[stored in .env.local]', 'Keep it secret !'],
                ['votix_key_secret', '[stored in .env.local]', 'Keep it secret !'],
                ['votix_result_password', $resultPassword, 'Note it without mistakes and keep it secret for now'],
            ])
        ;
        $table->render();

        return 0;
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    private function generateHumanPassword(int $length): string
    {
        assert($length > 3);

        // optimized keyspace for stressed people
        $keyspace1 = '23456789ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $keyspace2 = '#%+23456789:=?@ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $str = '';
        $max1 = mb_strlen($keyspace1, '8bit') - 1;
        $max2 = mb_strlen($keyspace2, '8bit') - 1;
        $str .= $keyspace1[random_int(0, $max1)];
        for ($i = 0; $i < $length - 2; ++$i) {
            $str .= $keyspace2[random_int(0, $max2)];
        }
        $str .= $keyspace1[random_int(0, $max1)];

        return $str;
    }
}
