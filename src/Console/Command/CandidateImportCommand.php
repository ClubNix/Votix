<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */

namespace App\Console\Command;

use App\Repository\CandidateRepository;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CandidateImportCommand
 */
class CandidateImportCommand extends Command
{
    /**
     * @var CandidateRepository
     */
    private $candidateRepository;

    public function __construct(CandidateRepository $candidateRepository)
    {
        $this->candidateRepository = $candidateRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:candidate:import')
            ->setDescription('Reset candidates from yaml')
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
     *
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filepath = $input->getArgument('filepath');

        $parsed = Yaml::parse(file_get_contents($filepath));

        foreach ($parsed['candidates'] as $candidate) {
            $output->writeln('name: ' . $candidate['name'] . ', eligible: ' . $candidate['eligible']);
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to overwrite candidates with these values ?', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('cancelled');
            return Command::FAILURE;
        }

        $this->candidateRepository->import($parsed['candidates']);

        $output->writeln('done');

        return Command::SUCCESS;
    }
}