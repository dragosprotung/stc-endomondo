<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\Test\API\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SportTrackerConnector\Core\Tracker\Exception\InvalidCredentialsException;
use SportTrackerConnector\Endomondo\API\Authentication;

/**
 * Test case for Authentication.
 */
class AuthenticationTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthenticationFromToken()
    {
        $token = 'my_token';
        $authentication = Authentication::withToken($token);

        self::assertSame($token, $authentication->token());
        self::assertSame($token, (string)$authentication);
    }

    public function testAuthenticationWithUsernameAndPasswordThrowsClientExceptionOnBadResponse()
    {
        $mockHandler = new MockHandler(
            [
                new ClientException('Client error', $this->createMock(Request::class)),
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $this->expectException(ClientException::class);

        Authentication::withUsernameAndPassword('username', 'password', $client);
    }

    public function testAuthenticationWithUsernameAndPasswordThrowsInvalidCredentialsExceptionOnBadCredentials()
    {
        $mockHandler = new MockHandler(
            [
                new Response(200, [], 'USER_UNKNOWN')
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $this->expectException(InvalidCredentialsException::class);

        Authentication::withUsernameAndPassword('username', 'password', $client);
    }

    public function testAuthenticationWithUsernameAndPassword()
    {
        $token = 'my_token';

        $mockHandler = new MockHandler(
            [
                new Response(200, [],
                    sprintf(
                        "OK\naction=PAIRED\nauthToken=%s\nmeasure=METRIC\ndisplayName=Dragos\nuserId=123\nsecureToken=st",
                        $token
                    )
                )
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);
        $authentication = Authentication::withUsernameAndPassword('username', 'password', $client);

        self::assertSame($token, $authentication->token());
        self::assertSame($token, (string)$authentication);
    }
}
