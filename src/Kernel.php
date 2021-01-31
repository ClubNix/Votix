<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use function dirname;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $c): void
    {
        // default to prod if env unknown
        $env = in_array($this->environment, ['dev', 'test', 'prod']) ? $this->environment : 'prod';

        $c->import('../config/{packages}/*.yaml');
        $c->import('../config/{packages}/'.$env.'/*.yaml');

        if (is_file(dirname(__DIR__).'/config/services.yaml')) {
            $c->import('../config/services.yaml');
            $c->import('../config/{services}_'.$env.'.yaml');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // default to prod if env unknown
        $env = in_array($this->environment, ['dev', 'test', 'prod']) ? $this->environment : 'prod';

        $routes->import('../config/{routes}/'.$env.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
    }
}
