<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MonitoringController
 */
class MonitoringController extends AbstractController
{
    /**
     * @Route("/healthz", name="healthz")
     *
     * @param Connection $connection
     *
     * @return Response
     *
     * @throws DBALException
     */
    public function index(Connection $connection): Response
    {
        $dbPing = $connection->query("select datetime('now', 'localtime')")->fetchColumn();
        var_dump($dbPing);

        return new Response('OK');
    }
}
