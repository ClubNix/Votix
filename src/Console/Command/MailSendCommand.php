<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use App\Entity\Voter;
use App\Repository\VoterRepository;
use App\Service\MailerService;
use App\Service\StatsService;
use Aws\Ses\SesClient;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class VotixMailSendCommand
 */
class MailSendCommand extends Command
{
    private LoggerInterface $logger;
    private VoterRepository $voterRepository;
    private StatsService $statsService;
    private MailerService $mailerService;
    private SesClient $sesClient;

    public function __construct(
        LoggerInterface $logger,
        VoterRepository $voterRepository,
        StatsService $statsService,
        MailerService $mailerService,
        SesClient $sesClient
    ) {
        $this->logger = $logger;
        $this->voterRepository = $voterRepository;
        $this->statsService = $statsService;
        $this->mailerService = $mailerService;
        $this->sesClient = $sesClient;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:mail:send')
            ->setDescription('Send mail')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template to use'
            )
            ->addOption(
                'execute',
                null,
                InputOption::VALUE_NONE,
                'If set will send mail for real, otherwise dry-run only'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $template    = $input->getArgument('template');
        $mustExecute = $input->getOption('execute');

        /** @var Voter[] $voters */
        $voters = $this->voterRepository->findBy(['ballot' => null]);

        $stats = $this->statsService->getStats();

        $nb_mails_to_send = $stats['nb_invites'] - $stats['nb_votants'];

        $this->logger->info('Number of mails to send : {nb_mails_to_send}', ['nb_mails_to_send' => $nb_mails_to_send]);

        $counter = 1;

        foreach ($voters as $voter) {
            $info = '> ' . $counter . '/' . $nb_mails_to_send . ' ' . $voter->getFirstname() . ' '. $voter->getLastname() . ' <' . $voter->getEmail() . '>';

            $this->logger->info($info);

            $email = $this->mailerService->getTemplatedEmail($voter, $template);

            if ($mustExecute) {
                $emailSentId = $this->sesClient->sendEmail($email);
                $message = 'Message envoyé à ' . $email['Destination']['ToAddresses'][0];
                $this->logger->info($message, ['mail' => $emailSentId->getAll()]);

                usleep(200000);
            } else {
                $this->logger->notice('Mail not sent because we are running in dry-run');
            }

            $counter++;
        }

        return 0;
    }
}