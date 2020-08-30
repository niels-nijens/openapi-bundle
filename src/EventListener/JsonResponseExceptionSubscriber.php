<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\EventListener;

use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Transforms an exception to a JSON response for OpenAPI routes.
 */
class JsonResponseExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilder;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelExceptionTransformToJsonResponse', 0],
            ],
        ];
    }

    /**
     * Constructs a new JsonResponseExceptionSubscriber instance.
     */
    public function __construct(ExceptionJsonResponseBuilderInterface $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Converts the exception to a JSON response.
     */
    public function onKernelExceptionTransformToJsonResponse(GetResponseForExceptionEvent $event): void
    {
        $routeOptions = $event->getRequest()->attributes->get(RouteContext::REQUEST_ATTRIBUTE);

        if (isset($routeOptions[RouteContext::RESOURCE]) === false) {
            return;
        }

        $event->setResponse($this->responseBuilder->build($event->getException()));
    }
}
