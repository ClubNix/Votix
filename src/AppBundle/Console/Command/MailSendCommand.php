<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Console\Command;

use AppBundle\Entity\Voter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Aws\Ses\SesClient;

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

        /** @var $logger LoggerInterface */
        $logger = $this->get('logger');

        /** @var Voter[] $voters */
        $voters = $this->get('votix.voter_repository')
                       ->findBy(['ballot' => null]);

        $stats = $this->get('votix.stats')->getStats();

        $nb_mails_to_send = $stats['nb_invites'] - $stats['nb_votants'];

        $output->writeln("<info>Number of mails to send : " . $nb_mails_to_send . "</info>");

        $client = SesClient::factory(
            [
            'key'    => '',
            'secret' => '',
            'region' => 'eu-west-1'
            ]
        );

        $counter = 1;

        foreach($voters as $voter) {
            $info = '> ' . $counter . '/' . $nb_mails_to_send . ' ' . $voter->getFirstname() . ' '. $voter->getLastname() . ' <' . $voter->getEmail() . '>';

            $output->writeln('<info>' . $info . '</info>');
            $logger->info($info);

            $email = $this->getTemplatedEmail($voter, $template);

            if($mustExecute) {
                $emailSentId = $client->sendEmail($email);
                $message = 'Message envoyé à ' . $email['Destination']['ToAddresses'][0];
                $output->writeln('<info>' . $message . '</info>');
                $logger->info('<info>' . $message . '</info>', $emailSentId->getAll());

                usleep(200000);
            } else {
                $output->writeln('<comment>Mail not sent because we are running in dry-run</comment>');
            }

            $counter++;
        }
    }

    /**
     * @param Voter $voter
     * @param $template
     * @return array
     */
    private function getTemplatedEmail($voter, $template) {
        $tokenService = $this->get('votix.token');

        $vars = [
            'voter' => $voter,
            'link'  => $tokenService->getLinkForVoter($voter),
            'code'  => $tokenService->getCodeForVoter($voter),
        ];

        $templateEngine = $this->get('templating');

        $html  = $templateEngine->render('mails/' . $template . '.html.twig',  $vars);
        $title = $templateEngine->render('mails/' . $template . '.title.twig', $vars);

        return $email = $this->getEmailForVoter($voter, $title, $html);
    }

    /**
     * @param Voter $voter
     * @param $title
     * @param $html
     * @return array
     */
    private function getEmailForVoter($voter, $title, $html) {
        $to = $voter->getFirstname() . ' ' . $voter->getLastname() . '<' . $voter->getEmail() . '>';

        $email = [
            'Source' => 'Votix <votix@clubnix.fr>',
            'Destination' => [
                'ToAddresses' => [$to]
            ],
            'Message' => [
                'Subject' => ['Data' => $title, 'Charset' => 'UTF-8'],
                'Body' => [
                    'Html' => ['Data' => $html, 'Charset' => 'UTF-8'],
                ],
            ],
            'ReplyToAddresses' => ['votix@clubnix.fr'],
            'ReturnPath'       => 'votix@clubnix.fr'
        ];

        return $email;
    }

}