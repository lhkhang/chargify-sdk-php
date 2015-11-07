<?php

/**
 * Copyright 2011 Crucial Web Studio, LLC or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * https://raw.githubusercontent.com/crucialwebstudio/chargify-sdk-php/master/LICENSE
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


namespace Crucial\Service;

use GuzzleHttp\Client,
    GuzzleHttp\Stream\Stream,
    GuzzleHttp\Exception\RequestException,
    Crucial\Service\ChargifyV2\Call,
    Crucial\Service\ChargifyV2\Direct,
    Crucial\Service\ChargifyV2\Exception\BadMethodCallException;

class ChargifyV2
{
    /**
     * The base URL for all api calls. NO TRAILING SLASH!
     *
     * @var string
     */
    protected $baseUrl = 'https://api.chargify.com/api/v2';

    /**
     * Your api_d
     *
     * @var string
     */
    protected $apiId;

    /**
     * Your api password
     *
     * @var string
     */
    protected $apiPassword;

    /**
     * Secret key
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * response expected from API
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * Config used in constructor.
     *
     * @var array
     */
    protected $config;

    /**
     * Last response received by the client
     *
     * @var \GuzzleHttp\Message\Response|false
     */
    protected $lastResponse;

    /**
     * Guzzle http client
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Initialize the service
     *
     * @param array $config
     */
    public function __construct($config)
    {
        // store a copy
        $this->config = $config;

        // set individual properties
        $this->apiId       = $config['api_id'];
        $this->apiPassword = $config['api_password'];
        $this->apiSecret   = $config['api_secret'];

        // set up http client
        $this->httpClient = new Client([
            'base_url' => $this->baseUrl,
            'defaults' => [
                'timeout'         => 10,
                'allow_redirects' => false,
                'auth'            => [$this->apiId, $this->apiPassword],
                'headers'         => [
                    'User-Agent'   => 'chargify-sdk-php/1.0 (https://github.com/crucialwebstudio/chargify-sdk-php)',
                    'Content-Type' => 'application/' . $this->format
                ]
            ]
        ]);
    }

    /**
     * Get the base URL for all requests made to the api.
     *
     * Does not contain a trailing slash.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Getter for api ID
     *
     * @return string
     */
    public function getApiId()
    {
        return $this->apiId;
    }

    /**
     * Getter for api secret.
     *
     * Be careful not to expose this to anyone, especially in your html.
     *
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * Returns config sent in constructor
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Getter for $this->httpClient
     *
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Send the request to Chargify
     *
     * @param string $path   URL path we are requesting such as: /subscriptions/<subscription_id>/adjustments
     * @param string $method GET, POST, PUST, DELETE
     * @param string $rawData
     * @param array  $params
     *
     * @return \GuzzleHttp\Message\Response|false Response object or false if there was no response (networking error,
     *                                            timeout, etc.)
     */
    public function request($path, $method, $rawData = NULL, $params = array())
    {
        $method  = strtoupper($method);
        $path    = ltrim($path, '/');
        $path    = '/' . $path . '.' . $this->format;
        $client  = $this->getHttpClient();
        $request = $client->createRequest($method, $path);

        // set headers if POST or PUT
        if (in_array($method, array('POST', 'PUT'))) {
            if (NULL === $rawData) {
                throw new BadMethodCallException('You must send raw data in a POST or PUT request');
            }

            if (!empty($params)) {
                $request->setQuery($params);
            }

            $request->setBody(Stream::factory($rawData));
        }

        // set headers if GET or DELETE
        if (in_array($method, array('GET', 'DELETE'))) {

            if (!empty($rawData)) {
                $request->setBody(Stream::factory($rawData));
            }

            if (!empty($params)) {
                $request->setQuery($params);
            }
        }

        try {
            $response = $client->send($request);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
            } else {
                $response = false;
            }
        }

        $this->lastResponse = $response;

        return $response;
    }

    /**
     * @return \GuzzleHttp\Message\Response|false
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Helper for instantiating an instance of Direct
     *
     * @return Direct
     */
    public function direct()
    {
        return new Direct($this);
    }

    /**
     * Helper for instantiating an instance of Call
     *
     * @return Call
     */
    public function call()
    {
        return new Call($this);
    }
}