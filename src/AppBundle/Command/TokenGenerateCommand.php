<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Command;

use AppBundle\Entity\Voter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixTokenGenerateCommand
 * @package AppBundle\Console\Command
 */
class TokenGenerateCommand extends AbstractCommand
{
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');

        /** @var Voter $voter */
        $voter = $this->get('votix.voter_repository')
                      ->findOneBy(['email' => $email]);

        if($voter == NULL) {
            $output->writeln("<error>L'addresse email n'a pas été trouvé chez les votants !</error>");
            return;
        }

        if($voter->hasVoted()) {
            $output->writeln("<warning>Ce votant a déjà voté !</warning>");
            return;
        }

        $tokenService = $this->get('votix.token');

        $token = $tokenService->getTokenForVoter($voter);
        $code  = $tokenService->getCodeForVoter($voter);

        var_dump($token);
        var_dump($code);
    }
}