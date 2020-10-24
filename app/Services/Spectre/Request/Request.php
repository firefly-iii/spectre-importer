<?php
/**
 * Request.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III Spectre importer
 * (https://github.com/firefly-iii/spectre-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);


namespace App\Services\Spectre\Request;

use App\Exceptions\ImportException;
use App\Exceptions\SpectreErrorException;
use App\Exceptions\SpectreHttpException;
use App\Services\Spectre\Response\Response;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use JsonException;
use Log;
use RuntimeException;

/**
 * Class Request
 */
abstract class Request
{
    /** @var int */
    protected $expiresAt = 0;
    /** @var string */
    private $appId;
    /** @var string */
    private $base;
    /** @var array */
    private $body;
    /** @var array */
    private $parameters;
    /** @var string */
    private $secret;
    private float $timeOut = 3.14;
    /** @var string */
    private $uri;

    /**
     * @return Response
     * @throws SpectreHttpException
     */
    abstract public function get(): Response;

    /**
     * @return Response
     * @throws SpectreHttpException
     */
    abstract public function post(): Response;

    /**
     * @return Response
     * @throws SpectreHttpException
     */
    abstract public function put(): Response;

    /**
     * @param array $body
     */
    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        Log::debug('setParameters', $parameters);
        $this->parameters = $parameters;
    }

    /**
     * @param float $timeOut
     */
    public function setTimeOut(float $timeOut): void
    {
        $this->timeOut = $timeOut;
    }

    /**
     * @return array
     * @throws SpectreHttpException
     * @throws SpectreErrorException
     */
    protected function authenticatedGet(): array
    {
        $fullUri = sprintf('%s/%s', $this->getBase(), $this->getUri());

        if (null !== $this->parameters) {
            $fullUri = sprintf('%s?%s', $fullUri, http_build_query($this->parameters));
        }
        $client = $this->getClient();
        $res    = null;
        $body   = null;
        $json   = null;
        try {
            $res = $client->request(
                'GET', $fullUri, [
                         'headers' => [
                             'Accept'       => 'application/json',
                             'Content-Type' => 'application/json',
                             'App-id'       => $this->getAppId(),
                             'Secret'       => $this->getSecret(),
                         ],
                     ]
            );
        } catch (TransferException $e) {
            Log::error(sprintf('TransferException: %s', $e->getMessage()));
            // if response, parse as error response.re
            if (!$e->hasResponse()) {
                throw new SpectreHttpException(sprintf('Exception: %s', $e->getMessage()));
            }
            $body = (string)$e->getResponse()->getBody();
            $json = [];
            try {
                $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Log::error(sprintf('Could not decode error: %s', $e->getMessage()));
            }

            $exception       = new SpectreErrorException;
            $exception->json = $json;
            throw $exception;
        }
        if (null !== $res && 200 !== $res->getStatusCode()) {
            // return body, class must handle this
            Log::error(sprintf('Status code is %d', $res->getStatusCode()));

            $body = (string)$res->getBody();
        }
        $body = $body ?? (string)$res->getBody();

        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new SpectreHttpException(
                sprintf(
                    'Could not decode JSON (%s). Error[%d] is: %s. Response: %s',
                    $fullUri,
                    $res ? $res->getStatusCode() : 0,
                    $e->getMessage(),
                    $body
                )
            );
        }

        if (null === $json) {
            throw new SpectreHttpException(sprintf('Body is empty. Status code is %d.', $res->getStatusCode()));
        }

        return $json;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @param string $base
     */
    public function setBase(string $base): void
    {
        $this->base = $base;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        // config here

        return new Client(
            [
                'connect_timeout' => $this->timeOut,
            ]
        );
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws ImportException
     */
    protected function sendSignedSpectrePost(array $data): array
    {
        if ('' === $this->uri) {
            throw new ImportException('No Spectre server defined');
        }
        $fullUri = sprintf('%s/%s', $this->getBase(), $this->getUri());
        $headers = $this->getDefaultHeaders();
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImportException($e->getMessage());
        }

        Log::debug('Final headers for spectre signed POST request:', $headers);
        try {
            $client = $this->getClient();
            $res    = $client->request('POST', $fullUri, ['headers' => $headers, 'body' => $body]);
        } catch (GuzzleException|Exception $e) {
            throw new ImportException(sprintf('Guzzle Exception: %s', $e->getMessage()));
        }

        try {
            $body = $res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from SpectreRequest::POST result: %s', $e->getMessage()));
            $body = '{}';
        }

        $statusCode      = $res->getStatusCode();
        $responseHeaders = $res->getHeaders();


        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImportException($e->getMessage());
        }
        $json['ResponseHeaders']    = $responseHeaders;
        $json['ResponseStatusCode'] = $statusCode;

        return $json;
    }

    /**
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        $userAgent       = sprintf('FireflyIII Spectre v%s', config('spectre.version'));
        $this->expiresAt = time() + 180;

        return [
            'App-id'        => $this->getAppId(),
            'Secret'        => $this->getSecret(),
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'Cache-Control' => 'no-cache',
            'User-Agent'    => $userAgent,
            'Expires-at'    => $this->expiresAt,
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws ImportException
     */
    protected function sendUnsignedSpectrePost(array $data): array
    {
        if ('' === $this->uri) {
            throw new ImportException('No Spectre server defined');
        }
        $fullUri = sprintf('%s/%s', $this->getBase(), $this->getUri());
        $headers = $this->getDefaultHeaders();
        $opts    = ['headers' => $headers];
        $body    = null;
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }
        if ('{}' !== (string)$body) {
            $opts['body'] = $body;
        }

        Log::debug('Final headers for spectre UNsigned POST request:', $headers);
        try {
            $client = $this->getClient();
            $res    = $client->request('POST', $fullUri, $opts);
        } catch (GuzzleException|Exception $e) {
            Log::error($e->getMessage());
            throw new ImportException(sprintf('Guzzle Exception: %s', $e->getMessage()));
        }

        try {
            $body = $res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from SpectreRequest::POST result: %s', $e->getMessage()));
            $body = '{}';
        }

        $statusCode      = $res->getStatusCode();
        $responseHeaders = $res->getHeaders();


        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImportException($e->getMessage());
        }
        $json['ResponseHeaders']    = $responseHeaders;
        $json['ResponseStatusCode'] = $statusCode;

        return $json;
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws ImportException
     */
    protected function sendUnsignedSpectrePut(array $data): array
    {
        if ('' === $this->uri) {
            throw new ImportException('No Spectre server defined');
        }
        $fullUri = sprintf('%s/%s', $this->getBase(), $this->getUri());
        $headers = $this->getDefaultHeaders();
        $opts    = ['headers' => $headers];
        $body    = null;

        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }
        if ('{}' !== (string)$body) {
            $opts['body'] = $body;
        }
        //Log::debug('Final body + headers for spectre UNsigned PUT request:', $opts);
        try {
            $client = $this->getClient();
            $res    = $client->request('PUT', $fullUri, $opts);
        } catch (RequestException|GuzzleException $e) {
            // get response.
            $response = $e->getResponse();
            if (null !== $response && 406 === $response->getStatusCode()) {
                // ignore it, just log it.
                $statusCode                 = $response->getStatusCode();
                $responseHeaders            = $response->getHeaders();
                $json                       = json_decode((string)$e->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $json['ResponseHeaders']    = $responseHeaders;
                $json['ResponseStatusCode'] = $statusCode;

                return $json;
            }
            Log::error($e->getMessage());
            Log::error((string)$e->getResponse()->getBody());
            throw new ImportException(sprintf('Request Exception: %s', $e->getMessage()));
        }

        try {
            $body = $res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from SpectreRequest::POST result: %s', $e->getMessage()));
            $body = '{}';
        }

        $statusCode      = $res->getStatusCode();
        $responseHeaders = $res->getHeaders();


        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImportException($e->getMessage());
        }
        $json['ResponseHeaders']    = $responseHeaders;
        $json['ResponseStatusCode'] = $statusCode;

        return $json;
    }
}
