<?php
/**
 * ConfigurationController.php
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


use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Spectre\Model\Account;
use App\Services\Spectre\Request\GetAccountsRequest as SpectreGetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use Log;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends Controller
{

    /**
     * @return ResponseFactory|Response
     */
    public function download()
    {
        // do something
        $result = '';
        $config = Configuration::fromArray(session()->get(Constants::CONFIGURATION))->toArray();
        try {
            $result = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, 512);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }

        $response = response($result);
        $name     = sprintf('spectre_import_config_%s.json', date('Y-m-d'));
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function index(Request $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Import from Spectre';
        $subTitle  = 'Configure your Spectre import';
        $pageTitle = 'Configuration';

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        $overruleSkip = 'true' === $request->get('overruleskip');
        if (null !== $configuration && true === $configuration->isSkipForm() && false === $overruleSkip) {
            // skipForm
            return redirect()->route('import.download.index');
        }

        // get list of asset accounts in Firefly III
        $url         = (string)config('spectre.url');
        $token       = (string)config('spectre.access_token');
        $accountList = new GetAccountsRequest($url, $token);

        $accountList->setVerify(config('spectre.connection.verify'));
        $accountList->setTimeOut(config('spectre.connection.timeout'));

        $accountList->setType(GetAccountsRequest::ASSET);
        $ff3Accounts = $accountList->get();

        // add Firefly III URL's:
        /** @var \GrumpyDictator\FFIIIApiSupport\Model\Account $ff3Account */
        foreach ($ff3Accounts as $ff3Account) {
            $ff3Account->url = sprintf('%saccounts/show/%d', config('spectre.url'), $ff3Account->id);
        }

        // get the accounts over at Spectre.
        $url                     = config('spectre.spectre_url');
        $appId                   = config('spectre.spectre_app_id');
        $secret                  = config('spectre.spectre_secret');
        $spectreList             = new SpectreGetAccountsRequest($url, $appId, $secret);
        $spectreList->connection = $configuration->getConnection();
        $spectreAccountList      = $spectreList->get();

        /** @var Account $spectreAccount */
        foreach ($spectreAccountList as $spectreAccount) {
            /** @var \GrumpyDictator\FFIIIApiSupport\Model\Account $ff3Account */
            foreach ($ff3Accounts as $ff3Account) {
                if ($spectreAccount->iban === $ff3Account->iban) {
                    $spectreAccount->matched = true;
                }
            }
        }

        // view for config:
        return view('import.configuration.index', compact('ff3Accounts', 'spectreAccountList', 'configuration', 'mainTitle', 'subTitle', 'pageTitle'));
    }

    /**
     * @param Request $request
     */
    public function post(ConfigurationPostRequest $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        // get config from request
        $fromRequest = $request->getAll();

        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // update config
        $configuration->setRules($fromRequest['rules']);
        $configuration->setSkipForm($fromRequest['skip_form']);
        if (null !== $fromRequest['date_not_after']) {
            $configuration->setDateNotAfter($fromRequest['date_not_after']->format('Y-m-d'));
        }
        if (null !== $fromRequest['date_not_before']) {
            $configuration->setDateNotBefore($fromRequest['date_not_before']->format('Y-m-d'));
        }
        $configuration->setDateRangeNumber($fromRequest['date_range_number']);
        $configuration->setDateRangeUnit($fromRequest['date_range_unit']);
        $configuration->setDateRange($fromRequest['date_range']);
        $configuration->setDoMapping($fromRequest['do_mapping']);
        $configuration->setIgnoreSpectreCategories($fromRequest['ignore_spectre_categories']);

        // loop accounts:
        $accounts = [];
        foreach (array_keys($fromRequest['do_import']) as $accountId) {
            if (isset($fromRequest['accounts'][$accountId])) {
                $accounts[$accountId] = (int)$fromRequest['accounts'][$accountId];
            }
        }
        $configuration->setAccounts($accounts);
        $configuration->updateDateRange();

        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set config as complete.
        session()->put(Constants::CONFIG_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.download.index');
    }
}
