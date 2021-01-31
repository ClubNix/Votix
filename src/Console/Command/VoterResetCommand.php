<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use App\Repository\VoterRepository;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class VoterResetCommand
 */
class VoterResetCommand extends Command
{
    /**
     * @var VoterRepository
     */
    public $voterRepository;

    public function __construct(VoterRepository $voterRepository)
    {
        $this->voterRepository = $voterRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:voter:reset')
            ->setDescription('Drop voters')
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
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to drop all voters ?', false);

        if ($helper->ask($input, $output, $question)) {
            $this->voterRepository->deleteAll();
            $output->writeln('done');
        } else {
            $output->writeln('cancelled');
        }

        return Command::SUCCESS;
    }
}