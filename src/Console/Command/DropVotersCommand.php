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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixDropVotersCommand
 */
class DropVotersCommand extends AbstractCommand
{
    public $voterRepository;

    public function __construct(VoterRepository $voterRepository)
    {
        $this->voterRepository = $voterRepository;
        parent::__construct();
    }

    protected function configure(): void
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
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->voterRepository->deleteAll();

        return 0;
    }
}