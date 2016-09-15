<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\API\Exception;

/**
 * Exception when the response from Endomondo is not expected.
 */
class BadResponseException extends \RuntimeException
{
}
