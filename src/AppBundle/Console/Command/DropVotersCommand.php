<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixDropVotersCommand
 * @package AppBundle\Console\Command
 */
class DropVotersCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('votix:drop')
            ->setDescription('Drop voters')
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
        $this->get('votix.voter_repository')->deleteAll();

        return 0;
    }
}