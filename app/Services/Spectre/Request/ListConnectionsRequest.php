<?php
/**
 * ListConnectionsRequest.php
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

use App\Exceptions\SpectreErrorException;
use App\Services\Spectre\Response\ErrorResponse;
use App\Services\Spectre\Response\ListConnectionsResponse;
use App\Services\Spectre\Response\Response;
use JsonException;
use Log;

/**
 * Class ListConnectionsRequest
 * TODO is not yet paginated.
 */
class ListConnectionsRequest extends Request
{
    /** @var string */
    public string $customer;

    /**
     * ListConnectionsRequest constructor.
     *
     * @param string $url
     * @param string $appId
     * @param string $secret
     */
    public function __construct(string $url, string $appId, string $secret)
    {
        $this->type = 'all';
        $this->setBase($url);
        $this->setAppId($appId);
        $this->setSecret($secret);
        $this->setUrl('connections');
    }

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function get(): Response
    {
        Log::debug('ListConnectionsRequest::get()');
        $this->setParameters(
            [
                'customer_id' => $this->customer,
            ]
        );
        try {
            $response = $this->authenticatedGet();
        } catch (SpectreErrorException $e) {
            // JSON thing.
            return new ErrorResponse($e->json ?? []);
        }

        return new ListConnectionsResponse($response['data']);
    }

    /**
     * @inheritDoc
     */
    public function post(): Response
    {
        // TODO: Implement post() method.
    }

    /**
     * @inheritDoc
     */
    public function put(): Response
    {
        // TODO: Implement put() method.
    }
}
