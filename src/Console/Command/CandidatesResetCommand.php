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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class VotixCandidatesResetCommand
 */
class CandidatesResetCommand extends Command
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
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filepath = $input->getArgument('filepath');

        $parsed = Yaml::parse(file_get_contents($filepath));

        $this->candidateRepository->import($parsed['candidates']);

        return 0;
    }
}