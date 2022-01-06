<?php

/*
 * Propstack PHP API
 *
 * Copyright (c) 2021 pdir / digital agentur // pdir GmbH
 *
 * @package    propstack-api
 * @link       https://github.com/pdir/propstack-api
 * @license    MIT
 * @author     Mathias Arzberger <develop@pdir.de>
 * @author     pdir GmbH <https://pdir.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pdir\Propstack;

use Pdir\Propstack\Exceptions\PropstackApiException;
use Pdir\Propstack\Exceptions\PropstackNoApiKeyException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class Api
{
    /**
     * the propstack api url
     *
     * @var string $apiUrl
     */
    private $api = 'https://api.propstack.de';

    /**
     * the propstack api version
     *
     * @var string $apiVersion
     */
    protected $apiVersion = 'v1';

    /**
     * the propstack api key
     *
     * @var string $apiKey
     */
    private $apiKey;

    /** @var Client */
    private $client;

    public function __construct(array $credentials = [])
    {
        var_dump(getenv('PROPSTACK_API_KEY'));

        $this->apiKey = $credentials['apiKey'] ?? getenv('PROPSTACK_API_KEY');

        $this->prepareClient();
    }

    public function getProjects(): ?array
    {
        $data = $this->requestGet('projects');

        if (null === $data) {
            return null;
        }

        return $data;
    }

    public function getSavedQueries(): ?array
    {
        $data = $this->requestGet('saved_queries');

        if (null === $data) {
            return null;
        }

        return $data;
    }

    private function requestGet($resource): array
    {
        /* @var Response $response */
        $response = $this->request('get', $resource);

        return $this->getArrayFromJsonBody($response);
    }

    /**
     * @throws PropstackNoApiKeyException
     * @throws PropstackApiException
     */
    public function request($method, $resource)
    {
        try {
            $response = $this->client->$method($this->createApiUrl($resource));
        } catch (\Exception $e) {
            if (401 === $e->getCode()) {
                $errJson = json_decode(strstr($e->getMessage(), '{'));
                throw new PropstackApiException($errJson->errors[0], 1, $e);
            }

            throw new PropstackApiException('Unknown exception', 0, $e);
        }

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        return $response;
    }

    /**
     * @param Response $response
     * @return array
     */
    private function getArrayFromJsonBody($response): array
    {
        $contents = $response->getBody()->getContents();
        return \GuzzleHttp\json_decode($contents, true);
    }

    /**
     * @param string $resource
     * @return string
     */
    protected function createApiUrl(string $resource = ''): string
    {
        return $this->api . '/' . $this->apiVersion . '/' . $resource . '?api_key=' . $this->apiKey;
    }

    private function prepareClient()
    {
        $client = new Client([
            'base_uri' => $this->createApiUrl(),
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
        ]);

        $this->client = $client;
    }
}
