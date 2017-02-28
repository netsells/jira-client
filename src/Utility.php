<?php

namespace Netsells\JiraClient;

class Utility
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getEpicFieldId()
    {
        $response = $this->client->requestApi('GET', 'field');

        if ($response->getStatusCode() == 200) {
            // Success
            $responseData = json_decode($response->getBody());

            foreach($responseData as $field) {
                if (isset($field->schema) && isset($field->schema->custom) && $field->schema->custom == 'com.pyxis.greenhopper.jira:gh-epic-link') {
                    return $field->key;
                }
            }
        }
    }

    public function getRankFieldId()
    {
        $response = $this->client->requestApi('GET', 'field');

        if ($response->getStatusCode() == 200) {
            // Success
            $responseData = json_decode($response->getBody());

            foreach($responseData as $field) {
                if (isset($field->schema) && isset($field->schema->custom) && $field->schema->custom == 'com.pyxis.greenhopper.jira:gh-lexo-rank') {
                    return $field->key;
                }
            }
        }
    }
}