<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use App\Service\StatsServiceInterface;
use App\Service\StatusServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckStatusCommand
 */
class CheckStatusCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StatusServiceInterface
     */
    private $statusService;

    /**
     * @var StatsServiceInterface
     */
    private $statsService;

    public function __construct(
        LoggerInterface $logger,
        StatusServiceInterface $statusService,
        StatsServiceInterface $statsService
    ) {
        $this->logger = $logger;
        $this->statusService = $statusService;
        $this->statsService = $statsService;

        parent::__construct();
    }

    protected function configure(): void
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Current status details', [
            'status'         => $this->statusService->getCurrentStatus(),
            'status_message' => $this->statusService->getCurrentStatusMessage(),
            'stats'          => $this->statsService->getStats(),
        ]);

        return 0;
    }
}
