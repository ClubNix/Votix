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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Class ConfigMailerCommand
 */
class ConfigMailerCommand extends Command
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
            ->setName('votix:config:mailer')
            ->setDescription('Test and save mailer config')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception|TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Be sure that your system time is correct or the configuration will fail !");
        $dotEnvFilepath = __DIR__ . '/../../../.env.local';

        if (!file_exists($dotEnvFilepath)) {
            $output->writeln("Config $dotEnvFilepath already exists. Exiting.");

            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you using Amazon SES? (Y/n) ', true);

        if ($helper->ask($input, $output, $question)) {
            $question = new Question('Please enter the ACCESS_KEY : ');
            $accessKey = trim($helper->ask($input, $output, $question));
            $question = new Question('Please enter the SECRET_KEY : ');
            $secretKey = trim($helper->ask($input, $output, $question));
            $question = new Question('Please enter the region (default: eu-west-1) : ', 'eu-west-1');
            $region = trim($helper->ask($input, $output, $question));

            $mailerDsn = 'ses+api://';
            $mailerDsn .= urlencode($accessKey);
            $mailerDsn .= ':';
            $mailerDsn .= urlencode($secretKey);
            $mailerDsn .= '@default?region=';
            $mailerDsn .= urlencode($region);

            $output->writeln("Generated DSN (for info): $mailerDsn");
        } else {
            $output->writeln('Pleaser refer to https://symfony.com/doc/current/mailer.html');
            $question = new Question('Pleaser enter the full MAILER_DSN : ');
            $mailerDsn = trim($helper->ask($input, $output, $question));
        }

        $question = new Question('Please enter the FROM header (name <mail>, ex. Votix <votix@domain.tld>): ');
        $from = trim($helper->ask($input, $output, $question));

        $question = new Question('Please enter the Reply-To header (eg votix@domain.tld): ');
        $replyTo = trim($helper->ask($input, $output, $question));

        $question = new Question('Please enter the Return-Path header (eg votix@domain.tld): ');
        $returnPath = trim($helper->ask($input, $output, $question));

        $question = new Question('Please enter YOUR OWN EMAIL for testing the configuration now: ');
        $testEmail = trim($helper->ask($input, $output, $question));

        $this->sendTestEmail($mailerDsn, $from, $testEmail, $replyTo, $returnPath);

        $question = new ConfirmationQuestion('Did you receive the email ? (Y/n) ', true);
        if ($helper->ask($input, $output, $question)) {
            $config = PHP_EOL;
            $config .= "MAILER_DSN=$mailerDsn" . PHP_EOL;
            $config .= "VOTIX_FROM=$from" . PHP_EOL;
            $config .= "VOTIX_REPLY_TO=$replyTo" . PHP_EOL;
            $config .= "VOTIX_RETURN_PATH=$returnPath" . PHP_EOL;

            file_put_contents($dotEnvFilepath, $config);

            $output->writeln('Configuration saved.');
        } else {
            $output->writeln('Configuration aborted. Please retry.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $mailerDsn
     * @param string $from
     * @param string $to
     * @param string $replyTo
     * @param string $returnPath
     * @throws TransportExceptionInterface
     */
    private function sendTestEmail(string $mailerDsn, string $from, string $to, string $replyTo, string $returnPath): void
    {
        $transport = Transport::fromDsn($mailerDsn);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->replyTo($replyTo)
            ->returnPath($returnPath)
            ->subject('Votix test email')
            ->text('If you receive this email, email is correctly configured.')
            ->html('<p>If you receive this email, email is correctly configured.</p>');

        $mailer->send($email);
    }
}
