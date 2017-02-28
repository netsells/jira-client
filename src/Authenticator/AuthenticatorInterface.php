<?php

namespace Netsells\JiraClient\Authenticator;

use GuzzleHttp\Client;

interface AuthenticatorInterface
{
    public function getClient($baseUrl) : Client;
}