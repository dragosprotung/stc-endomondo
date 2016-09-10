<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\API;

use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use SportTrackerConnector\Core\Tracker\Exception\InvalidCredentialsException;

class Authentication
{
    const URL_AUTHENTICATE = 'https://api.mobile.endomondo.com/mobile/auth';

    /**
     * @var string
     */
    protected $token;

    /**
     * @param string $token The token.
     */
    private function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function token() : string
    {
        return $this->token;
    }

    public static function fromToken(string $token) : Authentication
    {
        return new static($token);
    }

    /**
     * @param string $username
     * @param string $password
     * @param Client $client
     * @return Authentication If there is an error making the request.
     * @throws \SportTrackerConnector\Core\Tracker\Exception\InvalidCredentialsException
     */
    public static function fromUsernameAndPassword(string $username, string $password, Client $client) : Authentication
    {
        $response = $client->get(
            self::URL_AUTHENTICATE,
            array(
                'query' => array(
                    'country' => 'GB',
                    'action' => 'pair',
                    'deviceId' => (string)Uuid::uuid5(Uuid::NAMESPACE_DNS, gethostname()),
                    'email' => $username,
                    'password' => $password,
                )
            )
        );

        $responseBody = $response->getBody()->getContents();
        $response = parse_ini_string($responseBody);

        if (array_key_exists('authToken', $response)) {
            return new static($response['authToken']);
        }

        throw new InvalidCredentialsException('Authentication on Endomondo failed.');
    }

    public function __toString()
    {
        return $this->token();
    }
}
