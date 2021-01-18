<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Entity\Voter;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Class EmailBuilderService
 */
class EmailBuilderService
{
    private $tokenService;

    /** @var Environment */
    private $templateEngine;

    private $from;
    private $replyTo;
    private $returnPath;

    public function __construct(TokenServiceInterface $tokenService, Environment $templating, string $from, string $replyTo, string $returnPath)
    {
        $this->tokenService   = $tokenService;
        $this->templateEngine = $templating;

        $this->from = $from;
        $this->replyTo = $replyTo;
        $this->returnPath = $returnPath;
    }

    /**
     * @param Voter $voter
     * @param string $template
     *
     * @return Email
     *
     * @throws TwigError
     */
    public function getTemplatedEmail(Voter $voter, string $template): Email
    {
        $tokenService = $this->tokenService;

        $vars = [
            'voter' => $voter,
            'link'  => $tokenService->getLinkForVoter($voter),
            'code'  => $tokenService->getCodeForVoter($voter),
        ];

        $html  = $this->templateEngine->render('mails/' . $template . '.html.twig',  $vars);
        $title = $this->templateEngine->render('mails/' . $template . '.title.twig', $vars);

        var_dump($template);
        var_dump($template);

        return $this->getEmailForVoter($voter, $title, $html);
    }

    /**
     * @param Voter $voter
     * @param string $title
     * @param string $html
     *
     * @return Email
     */
    private function getEmailForVoter(Voter $voter, string $title, string $html): Email
    {
        $to = $voter->getFirstname() . ' ' . $voter->getLastname() . '<' . $voter->getEmail() . '>';

        return (new Email())
            ->from($this->from)
            ->to($to)
            ->replyTo($this->replyTo)
            ->returnPath($this->returnPath)
            ->subject($title)
            ->html($html);
    }
}