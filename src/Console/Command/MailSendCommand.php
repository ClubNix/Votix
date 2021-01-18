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
use App\Service\EmailBuilderService;
use App\Service\StatsService;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Error\Error as TwigError;

/**
 * Class VotixMailSendCommand
 */
class MailSendCommand extends Command
{
    private $logger;

    private $voterRepository;

    private $statsService;

    private $emailBuilderService;

    private $mailer;

    public function __construct(
        LoggerInterface $logger,
        VoterRepository $voterRepository,
        StatsService $statsService,
        EmailBuilderService $emailBuilderService,
        MailerInterface $mailer
    ) {
        $this->logger = $logger;
        $this->voterRepository = $voterRepository;
        $this->statsService = $statsService;
        $this->emailBuilderService = $emailBuilderService;
        $this->mailer = $mailer;

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
     * @throws TwigError
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $template    = $input->getArgument('template');
        $mustExecute = $input->getOption('execute');

        /** @var Voter[] $voters */
        $voters = $this->voterRepository->findBy(['ballot' => null]);

        $stats = $this->statsService->getStats();

        $nb_mails_to_send = $stats['nb_invites'] - $stats['nb_votants'];

        $this->logger->notice('Number of mails to send : {nb_mails_to_send}', ['nb_mails_to_send' => $nb_mails_to_send]);

        $counterSent = 0;
        $counter = 1;

        foreach ($voters as $voter) {
            $info = '> ' . $counter . '/' . $nb_mails_to_send . ' ' . $voter->getFirstname() . ' '. $voter->getLastname() . ' <' . $voter->getEmail() . '>';

            $this->logger->notice($info);

            $email = $this->emailBuilderService->getTemplatedEmail($voter, $template);

            if ($mustExecute) {
                try {
                    $this->mailer->send($email);
                    $message = 'Message envoyé à ' . $voter->getEmail();
                    $this->logger->notice($message, ['mail' => $voter->getEmail()]);
                    $counterSent++;
                } catch (TransportExceptionInterface $e) {
                    $message = 'Message NON envoyé à ' . $voter->getEmail();
                    $this->logger->emergency($message, ['mail' => $voter->getEmail()]);
                    throw $e;
                }

                usleep(200000);
            } else {
                $this->logger->notice('Mail not sent because we are running in dry-run');
            }

            $counter++;
        }

        $this->logger->notice('Number of mails to sent : {nb_mails_sent}', ['nb_mails_sent' => $counterSent]);

        return 0;
    }
}