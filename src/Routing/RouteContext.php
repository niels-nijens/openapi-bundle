<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Routing;

/**
 * Contains the context keys added by the {@see RouteLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface RouteContext
{
    public const REQUEST_ATTRIBUTE = '_nijens_openapi';

    public const RESOURCE = 'openapi_resource';

    public const JSON_REQUEST_VALIDATION_POINTER = 'openapi_json_request_validation_pointer';
}
