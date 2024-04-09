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

namespace Nijens\OpenapiBundle\Tests\Functional\ExceptionHandling;

use Nijens\OpenapiBundle\NijensOpenapiBundle;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of error responses created by the exception handling feature.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ErrorResponsesTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'exception_handling']);
    }

    public function testCanReturnProblemJsonObjectForTriggeredError(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/trigger-error',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":500,"detail":"This is an error triggered by the OpenAPI bundle test suite."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForThrownError(): void
    {
        if (NijensOpenapiBundle::getSymfonyVersion() < 70000) {
            /*
             * Insulating the client to prevent PHPUnit from catching the error before
             * the ThrowableToProblemExceptionSubscriber and ProblemExceptionToJsonResponseSubscriber.
             */
            $this->client->insulate();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('OUTPUT: {"type":"about:blank","title":"An error occurred.","status":500,"detail":"This is an error thrown by the OpenAPI bundle test suite."} ERROR OUTPUT: .');
        }

        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-error',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":500,"detail":"This is an error thrown by the OpenAPI bundle test suite."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForThrownHttpException(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-http-exception',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_SERVICE_UNAVAILABLE);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":503,"detail":"This is an HTTP exception thrown by the OpenAPI bundle test suite."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForThrownException(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-exception',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":500,"detail":"This is an exception thrown by the OpenAPI bundle test suite."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForThrownInvalidArgumentExceptionWithAdditionalInformation(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-invalid-argument-exception',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            '{"type":"https://example.com/invalid-error","title":"The request was invalid.","status":400,"detail":"No valid request body provided.","instance":"/api/error/throw-invalid-argument-exception"}',
            $this->client->getResponse()->getContent()
        );
    }
}
