<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Console\Command;

use App\Command\GenerateTokenCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixTokenGenerateCommand
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

        $command = new GenerateTokenCommand($email);

        $response = $this->send($command);

        echo $response->getBody($asString = true);

        return 0;
    }
}