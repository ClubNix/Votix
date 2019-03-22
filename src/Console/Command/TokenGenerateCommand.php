<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixTokenGenerateCommand
 */
class TokenGenerateCommand extends AbstractCommand
{
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
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

        $this->logger->info('hello world2');

        return 0;
    }
}