<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

use AppBundle\Entity\Voter;
use Twig_Environment;

/**
 * Class MailerService
 * @package AppBundle\Service
 */
class MailerService
{

    private $token;

    /** @var Twig_Environment */
    private $templating;

    public function __construct(TokenService $token, $templating)
    {
        $this->token      = $token;
        $this->templating = $templating;
    }

    /**
     * @param Voter $voter
     * @param $template
     * @return array
     */
    public function getTemplatedEmail($voter, $template) {
        $tokenService = $this->token;

        $vars = [
            'voter' => $voter,
            'link'  => $tokenService->getLinkForVoter($voter),
            'code'  => $tokenService->getCodeForVoter($voter),
        ];

        $templateEngine = $this->templating;

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