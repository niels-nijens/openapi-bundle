<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Routing;

use Symfony\Component\HttpKernel\Kernel;

if (Kernel::MAJOR_VERSION < 5) {
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
} else {
    /**
     * {@see AbstractRouteLoader} implementation for Symfony version 5.x.
     *
     * @author Niels Nijens <nijens.niels@gmail.com>
     */
    class RouteLoader extends AbstractRouteLoader
    {
        /**
         * {@inheritdoc}
         */
        public function supports($resource, string $type = null)
        {
            return parent::doSupports($resource, $type);
        }

        /**
         * {@inheritdoc}
         */
        public function load($resource, string $type = null)
        {
            return parent::doLoad($resource, $type);
        }
    }
}
