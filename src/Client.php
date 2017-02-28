<?php

namespace Netsells\JiraClient;

use Closure;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as GuzzleClient;
use Netsells\JiraClient\Authenticator\AuthenticatorInterface;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;
    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    public function __construct($baseUrl, AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->client = $this->authenticator->getClient($baseUrl);
    }

    public function request($method, $url, $params = [])
    {
        $params['headers'][] = ['Accept' => 'application/json'];
        $params['headers'][] = ['Content-Type' => 'application/json'];

        return $this->client->request($method, $url, $params);
    }

    public function requestApi($method, $url, $params = [])
    {
        return $this->request($method, "rest/api/2/{$url}", $params);
    }

    public function send(Request $request)
    {
        return $this->client->send($request);
    }

    public function paginateApi($method, $url, $params = [], Closure $callback = null)
    {
        $response = [];

        $responseObject = $this->requestApi($method, $url, $params);
        $responseData = json_decode($responseObject->getBody());

        $newStartAt = $responseData->startAt;
        $maxResults = $responseData->maxResults;
        $totalResults = $responseData->total;

        if ($callback) {
            $callback($responseData);
        } else {
            $response[] = $responseData;
        }

        while ($newStartAt + $maxResults < $totalResults) {
            $newStartAt = $responseData->startAt + $responseData->maxResults;

            $params['json']['startAt'] = $newStartAt;

            $responseObject = $this->requestApi($method, $url, $params);
            $responseData = json_decode($responseObject->getBody());

            if ($callback) {
                $callback($responseData);
            } else {
                $response[] = $responseData;
            }
        }

        return $response;
    }

    public function searchWithJql($jql, Closure $callback, $fields = [])
    {
        return $this->paginateApi('POST', 'search', ['json' => ['jql' => $jql, 'fields' => $fields]], $callback);
    }
}