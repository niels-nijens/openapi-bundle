<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Routing;

/**
 * {@see AbstractRouteLoader} implementation for Symfony versions 3.4 and 4.4.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RouteLoader extends AbstractRouteLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return parent::doSupports($resource, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        return parent::doLoad($resource, $type);
    }
}
