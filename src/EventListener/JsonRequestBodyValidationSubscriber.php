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

use Exception;
use JsonSchema\Validator;
use League\JsonReference\DereferencerInterface;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Validates a JSON request body for routes loaded through the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonRequestBodyValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var JsonParser
     */
    private $jsonParser;

    /**
     * @var DereferencerInterface
     */
    private $dereferencer;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(
                array('validateRequestBody', 28),
            ),
        );
    }

    /**
     * Constructs a new JsonRequestBodyValidationSubscriber instance.
     *
     * @param RouterInterface       $router
     * @param JsonParser            $jsonParser
     * @param DereferencerInterface $dereferencer
     */
    public function __construct(RouterInterface $router, JsonParser $jsonParser, DereferencerInterface $dereferencer)
    {
        $this->router = $router;
        $this->jsonParser = $jsonParser;
        $this->dereferencer = $dereferencer;
    }

    /**
     * Validates the body of a request to an OpenAPI specification route. Throws an exception when validation failed.
     *
     * @param GetResponseEvent $event
     */
    public function validateRequestBody(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $requestContentType = $request->headers->get('Content-Type');

        $route = $this->router->getRouteCollection()->get(
            $request->attributes->get('_route')
        );

        if ($route instanceof Route === false) {
            return;
        }

        if ($route->hasOption('openapi_resource') === false || $route->hasOption('openapi_json_request_validation_pointer') === false) {
            return;
        }

        if ($requestContentType !== 'application/json') {
            $this->throwInvalidJsonException();
        }

        $requestBody = $request->getContent();
        $decodedJsonRequestBody = $this->validateJsonRequestBody($requestBody);

        $this->validateJsonAgainstSchema($route, $decodedJsonRequestBody);
    }

    /**
     * Validates if the request body is valid JSON.
     *
     * @param string $requestBody
     *
     * @return mixed
     */
    private function validateJsonRequestBody(string $requestBody)
    {
        $decodedJsonRequestBody = json_decode($requestBody);
        if ($decodedJsonRequestBody !== null || $requestBody === 'null') {
            return $decodedJsonRequestBody;
        }

        $exception = $this->jsonParser->lint($requestBody);
        if ($exception instanceof ParsingException) {
            $this->throwInvalidJsonException($exception);
        }
    }

    /**
     * Validates the JSON request body against the JSON Schema within the OpenAPI specification.
     *
     * @param Route $route
     * @param mixed $decodedJsonRequestBody
     */
    private function validateJsonAgainstSchema(Route $route, $decodedJsonRequestBody)
    {
        $schema = $this->dereferencer->dereference('file://'.$route->getOption('openapi_resource'));

        $jsonPointer = new JsonPointer($schema);
        $jsonSchema = $jsonPointer->get($route->getOption('openapi_json_request_validation_pointer'));

        $validator = new Validator();
        $validator->validate($decodedJsonRequestBody, $jsonSchema);

        if ($validator->isValid() === false) {
            $this->throwInvalidRequestException($validator->getErrors());
        }
    }

    /**
     * @param Exception|null $previousException
     */
    private function throwInvalidJsonException(Exception $previousException = null): void
    {
        $exception = new InvalidRequestHttpException('The request body should be valid JSON.', $previousException);
        if ($previousException instanceof ParsingException) {
            $exception->setErrors(array(
                $previousException->getMessage(),
            ));
        }

        throw $exception;
    }

    /**
     * @param array $errors
     */
    private function throwInvalidRequestException(array $errors): void
    {
        $errorMessages = array_map(
            function ($error) {
                return $error['message'];
            },
            $errors
        );

        $exception = new InvalidRequestHttpException('Validation of JSON request body failed.');
        $exception->setErrors($errorMessages);

        throw $exception;
    }
}
