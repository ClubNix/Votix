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
use App\Service\TokenService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixTokenGenerateCommand
 */
class TokenGenerateCommand extends Command
{
    /**
     * @var VoterRepository
     */
    private $voterRepository;

    /**
     * @var TokenService
     */
    private $tokenService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        VoterRepository $voterRepository,
        TokenService $tokenService,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->voterRepository = $voterRepository;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:token:generate')
            ->setDescription('Generate token for email')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Email to generate token to'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        /** @var Voter $voter */
        $voter = $this->voterRepository->findOneBy(['email' => $email]);

        if ($voter === NULL) {
            $this->logger->error("L'addresse email n'a pas été trouvé chez les votants !");
            return 1;
        }

        if ($voter->hasVoted()) {
            $this->logger->warning('Ce votant a déjà voté !');
            return 1;
        }

        $token = $this->tokenService->getTokenForVoter($voter);
        $code  = $this->tokenService->getCodeForVoter($voter);

        $this->logger->info($token);
        $this->logger->info($code);

        return 0;
    }
}