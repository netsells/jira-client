<?php

namespace Netsells\JiraClient\Authenticator;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Netsells\JiraClient\Guzzle\Oauth1;

class OAuth implements AuthenticatorInterface
{
    protected $baseUrl;
    protected $sandbox;
    protected $consumerKey;
    protected $consumerSecret;
    protected $callbackUrl;
    protected $requestTokenUrl = 'plugins/servlet/oauth/request-token';
    protected $accessTokenUrl = 'plugins/servlet/oauth/access-token';
    protected $authorizationUrl = 'plugins/servlet/oauth/authorize?oauth_token=%s';
    protected $privateKeyPassphrase = null;

    protected $tokens;

    protected $client;
    protected $oauthPlugin;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function requestTempCredentials()
    {
        return $this->requestCredentials(
            $this->requestTokenUrl.'?oauth_callback='.$this->callbackUrl
        );
    }

    public function requestAuthCredentials($token, $tokenSecret, $verifier)
    {
        return $this->requestCredentials(
            $this->accessTokenUrl.'?oauth_callback='.$this->callbackUrl.'&oauth_verifier='.$verifier,
            $token,
            $tokenSecret
        );
    }

    protected function requestCredentials($url, $token = false, $tokenSecret = false)
    {
        $client = $this->getClient($token, $tokenSecret);

        $response = $client->post($url);

        return $this->makeTokens($response);
    }

    protected function makeTokens($response)
    {
        $body = (string) $response->getBody();

        $tokens = array();
        parse_str($body, $tokens);

        if (empty($tokens)) {
            throw new Exception('An error occurred while requesting oauth token credentials');
        }

        $this->tokens = $tokens;

        return $this->tokens;
    }

    public function setToken($token = null, $tokenSecret = null)
    {
        if ($token) {
            $this->tokens['oauth_token'] = $token;
        }

        if ($tokenSecret) {
            $this->tokens['oauth_token_secret'] = $tokenSecret;
        }
    }

    public function getClient()
    {
        if (!is_null($this->client)) {
            return $this->client;
        } else {
            $stack = HandlerStack::create();

            $middleware = new Oauth1([
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
                'token' => $this->tokens['oauth_token'],
                'token_secret' => $this->tokens['oauth_token_secret'],
                'private_key' => $this->privateKey,
                'private_key_passphrase' => $this->privateKeyPassphrase,
                'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
            ]);
            $stack->push($middleware);

            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'handler' => $stack,
                'auth' => 'oauth',
            ]);

            return $this->client;
        }
    }

    public function makeAuthUrl()
    {
        return $this->baseUrl.sprintf($this->authorizationUrl, urlencode($this->tokens['oauth_token']));
    }

    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    public function setPrivateKeyPassphrase($privateKeyPassphrase)
    {
        $this->privateKeyPassphrase = $privateKeyPassphrase;

        return $this;
    }
}