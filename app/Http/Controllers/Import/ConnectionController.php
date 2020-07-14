<?php
/**
 * ConnectionController.php
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

namespace App\Http\Controllers\Import;


use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ConnectionSelected;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Spectre\Model\Customer;
use App\Services\Spectre\Request\ListConnectionsRequest;
use App\Services\Spectre\Request\ListCustomersRequest;
use App\Services\Spectre\Request\PostConnectSessionsRequest;
use App\Services\Spectre\Request\PostCustomerRequest;
use App\Services\Spectre\Request\PutRefreshConnectionRequest;
use App\Services\Spectre\Response\ErrorResponse;
use App\Services\Spectre\Response\PostConnectSessionResponse;
use App\Services\Spectre\Response\PostCustomerResponse;
use App\Services\Storage\StorageService;
use Illuminate\Http\Request;
use JsonException;
use Log;

/**
 * Class ConnectionController
 */
class ConnectionController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(ConnectionSelected::class);
        app('view')->share('pageTitle', 'Connection selection      nice ey?');
    }

    /**
     *
     */
    public function index()
    {
        $mainTitle = 'Spectre';
        $subTitle  = 'Select your financial organisation';
        $uri       = config('spectre.spectre_uri');
        $appId     = config('spectre.spectre_app_id');
        $secret    = config('spectre.spectre_secret');

        // check if already has the correct customer:
        $hasCustomer = false;
        $request     = new ListCustomersRequest($uri, $appId, $secret);
        $list        = $request->get();
        $identifier  = null;

        if ($list instanceof ErrorResponse) {
            throw new ImportException(sprintf('%s: %s', $list->class, $list->message));
        }
        /** @var Customer $item */
        foreach ($list as $item) {
            if ('default_ff3_customer' === $item->identifier) {
                $hasCustomer = true;
                $identifier  = $item->id;
            }
        }

        if (false === $hasCustomer) {
            // create new one
            $request             = new PostCustomerRequest($uri, $appId, $secret);
            $request->identifier = 'default_ff3_customer';
            /** @var PostCustomerResponse $customer */
            $customer   = $request->post();
            $identifier = $customer->customer->id;
        }

        // store identifier in config
        // skip next time?
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        $configuration->setIdentifier($identifier);

        // save config
        $json = '[]';
        try {
            $json = json_encode($configuration, JSON_THROW_ON_ERROR, 512);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }
        StorageService::storeContent($json);

        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        Log::debug('About to get connections.');
        $request           = new ListConnectionsRequest($uri, $appId, $secret);
        $request->customer = (string) $identifier;
        $list              = $request->get();

        if ($list instanceof ErrorResponse) {
            throw new ImportException(sprintf('%s: %s', $list->class, $list->message));
        }

        return view('import.connection.index', compact('mainTitle', 'subTitle', 'list', 'identifier', 'configuration'));
    }

    /**
     * @param Request $request
     */
    public function post(Request $request)
    {
        $connectionId = $request->get('spectre_connection_id');

        if ('00' === $connectionId) {
            // get identifier
            $configuration = Configuration::fromArray([]);
            if (session()->has(Constants::CONFIGURATION)) {
                $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
            }

            // make a new connection.
            $uri                = config('spectre.spectre_uri');
            $appId              = config('spectre.spectre_app_id');
            $secret             = config('spectre.spectre_secret');
            $newToken           = new PostConnectSessionsRequest($uri, $appId, $secret);
            $newToken->customer = $configuration->getIdentifier();
            $newToken->uri      = route('import.callback.index');
            /** @var PostConnectSessionResponse $result */
            $result             = $newToken->post();
            return redirect($result->connect_url);
        }

        // store connection in config, go to fancy JS page.
        // store identifier in config
        // skip next time?
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        $configuration->setConnection($connectionId);

        // save config
        $json = '[]';
        try {
            $json = json_encode($configuration, JSON_THROW_ON_ERROR, 512);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }
        StorageService::storeContent($json);

        session()->put(Constants::CONFIGURATION, $configuration->toArray());
        session()->put(Constants::CONNECTION_SELECTED_INDICATOR, 'true');

        // redirect to job configuration
        return redirect(route('import.configure.index'));

    }

}
