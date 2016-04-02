<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class VotixCandidatesResetCommand
 * @package AppBundle\Console\Command
 */
class CandidatesResetCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('votix:candidates:reset')
            ->setDescription('Reset candidates')
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'Yaml file to import candidates'
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
        $filepath = $input->getArgument('filepath');

        $parsed = Yaml::parse(file_get_contents($filepath));

        $this->get('votix.candidate_repository')->import($parsed['candidates']);

        return 0;
    }
}