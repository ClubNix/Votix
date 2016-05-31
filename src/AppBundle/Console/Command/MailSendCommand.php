<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Console\Command;

use AppBundle\Command\SendMailCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixMailSendCommand
 * @package AppBundle\Console\Command
 */
class MailSendCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('votix:mail:send')
            ->setDescription('Send mail')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template to use'
            )
            ->addOption(
                'execute',
                null,
                InputOption::VALUE_NONE,
                'If set will send mail'
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
        $template    = $input->getArgument('template');
        $mustExecute = $input->getOption('execute');

        $command = new SendMailCommand($template, null, !$mustExecute);

        $response = $this->send($command);

        echo $response->getBody($asString = true);
    }
}