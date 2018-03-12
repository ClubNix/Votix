<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckStatusCommand
 */
class CheckStatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('votix:check-status')
            ->setDescription('Check status')
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
        $command = new \App\Command\CheckStatusCommand();

        $response = $this->send($command);

        echo $response->getBody($asString = true);

        return 0;
    }
}