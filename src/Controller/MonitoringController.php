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
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Statement;
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
     * @throws DBALDriverException
     */
    public function index(Connection $connection): Response
    {
        /** @var Statement $stmt */
        $stmt = $connection->executeQuery("select datetime('now', 'localtime')");

        $result = $stmt->fetchOne();

        return new Response('OK ' . $result);
    }
}
