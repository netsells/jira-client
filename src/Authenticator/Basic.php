<?php

namespace Netsells\JiraClient\Authenticator;

use GuzzleHttp\Client;

class Basic implements AuthenticatorInterface
{
    private $username;
    private $password;

    public function __construct($username, $password)
    {
        $this->setLogin($username, $password);
    }

    public function setLogin($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getClient($baseUrl): Client
    {
        return new Client([
            'base_uri' => $baseUrl,
            'auth' => [$this->username, $this->password],
        ]);
    }
}