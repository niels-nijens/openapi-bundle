<?php

declare(strict_types=1);

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\DependencyInjection;

use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ProblemExceptionToJsonResponseSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ThrowableToProblemExceptionSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformer;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use Nijens\OpenapiBundle\Validation\EventSubscriber\RequestValidationSubscriber;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestParameterValidator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Loads and manages the bundle configuration and services.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class NijensOpenapiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->loadDeprecatedServices($loader);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerRoutingConfiguration($config['routing'], $container);
        $this->registerValidationConfiguration($config['validation'], $container);
        $this->registerExceptionHandlingConfiguration($config['exception_handling'], $container);

        if ($config['validation']['enabled'] === true && $config['exception_handling']['enabled'] !== true) {
            trigger_error(
                'Enabling the validation component without the exception handling component might cause unexpected results.',
            );
        }
    }

    /**
     * Loads the deprecated services file.
     */
    private function loadDeprecatedServices(XmlFileLoader $loader): void
    {
        $loader->load('services_deprecated.xml');
    }

    private function registerRoutingConfiguration(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(RouteLoader::class);
        $definition->replaceArgument(2, $config['operation_id_as_route_name']);
    }

    private function registerValidationConfiguration(array $config, ContainerBuilder $container): void
    {
        if ($config['enabled'] !== true) {
            $container->removeDefinition(RequestValidationSubscriber::class);
        }

        if ($config['parameter_validation'] === false) {
            $container->removeDefinition(RequestParameterValidator::class);
        }
    }

    private function registerExceptionHandlingConfiguration(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ThrowableToProblemExceptionTransformer::class);
        $definition->replaceArgument(
            0,
            array_replace_recursive(Configuration::DEFAULT_EXCEPTION_HANDLING_EXCEPTIONS, $config['exceptions'])
        );

        if ($config['enabled'] !== true) {
            $container->removeDefinition(ThrowableToProblemExceptionSubscriber::class);
            $container->removeDefinition(ProblemExceptionToJsonResponseSubscriber::class);
        }
    }
}
