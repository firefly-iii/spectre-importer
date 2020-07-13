<?php
/**
 * GenerateTransactions.php
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

namespace App\Services\Sync;

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\ProgressInformation;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Account;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountRequest;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountResponse;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use Log;

/**
 * Class GenerateTransactions.
 */
class GenerateTransactions
{
    use ProgressInformation;

    /** @var array */
    private $accounts;
    /** @var Configuration */
    private $configuration;
    /** @var string[] */
    private $specialSubTypes = ['REVERSAL', 'REQUEST', 'BILLING', 'SCT', 'SDD', 'NLO'];
    /** @var array */
    private $targetAccounts;
    /** @var array */
    private $targetTypes;

    /**
     * GenerateTransactions constructor.
     */
    public function __construct()
    {
        $this->targetAccounts = [];
        $this->targetTypes    = [];
        bcscale(12);
    }

    /**
     *
     */
    public function collectTargetAccounts(): void
    {
        Log::debug('Going to collect all target accounts from Firefly III.');
        // send account list request to Firefly III.
        $token   = (string) config('spectre.access_token');
        $uri     = (string) config('spectre.uri');
        $request = new GetAccountsRequest($uri, $token);
        /** @var GetAccountsResponse $result */
        $result = $request->get();
        $return = [];
        $types  = [];
        /** @var Account $entry */
        foreach ($result as $entry) {
            $type = $entry->type;
            if (in_array($type, ['reconciliation', 'initial-balance', 'expense', 'revenue'], true)) {
                continue;
            }
            $iban = $entry->iban;
            if ('' === (string) $iban) {
                continue;
            }
            Log::debug(sprintf('Collected %s (%s) under ID #%d', $iban, $entry->type, $entry->id));
            $return[$iban] = $entry->id;
            $types[$iban]  = $entry->type;
        }
        $this->targetAccounts = $return;
        $this->targetTypes    = $types;
        Log::debug(sprintf('Collected %d accounts.', count($this->targetAccounts)));
    }

    /**
     * @param array $spectre
     *
     * @throws ImportException
     * @return array
     */
    public function getTransactions(array $spectre): array
    {
        $return = [];
        /**
         * @var string $spectreAccountId
         * @var array $entries
         */
        foreach ($spectre as $spectreAccountId => $entries) {
            $spectreAccountId = (string)$spectreAccountId;
            app('log')->debug(sprintf('Going to parse account #%s', $spectreAccountId));
            foreach ($entries as $entry) {
                $return[] = $this->generateTransaction($spectreAccountId, $entry);
                // TODO error handling at this point.
            }
        }
        $this->addMessage(0, sprintf('Parsed %d Spectre transactions for further processing.', count($return)));

        return $return;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->accounts      = $configuration->getAccounts();
    }

    /**
     * @param string   $spectreAccountId
     * @param array $entry
     *
     * @throws ImportException
     * @return array
     */
    private function generateTransaction(string $spectreAccountId, array $entry): array
    {

        Log::debug('Original Spectre transaction', $entry);
        $return = [
            'apply_rules'             => $this->configuration->isRules(),
            'error_if_duplicate_hash' => true,
            'transactions'            => [
                [
                    'type'          => 'withdrawal', // reverse
                    'date'          => str_replace('T', ' ', substr($entry['made_on'], 0, 19)),
                    'datetime'      => $entry['made_on'], // not used in API, only for transaction filtering.
                    'amount'        => 0,
                    'description'   => $entry['description'],
                    'order'         => 0,
                    'currency_code' => $entry['currency_code'],
                    'tags'          => [$entry['mode'], $entry['status'], $entry['category']],
                    'category_name' => $entry['category'],
                    'category_id'   => $this->configuration->getMapping()['categories'][$entry['category']] ?? null,
                ],
            ],
        ];
        if($this->configuration->isIgnoreSpectreCategories()) {
            unset($return['transactions'][0]['tags'], $return['transactions'][0]['category_name'], $return['transactions'][0]['category_id']);
        }
        // save meta:
        $return['transactions'][0]['external_id']        = $entry['id'];
        $return['transactions'][0]['internal_reference'] = $spectreAccountId;


        if (1 === bccomp($entry['amount'], '0')) {
            // amount is positive: deposit or transfer. Spectre account is destination
            $return['transactions'][0]['type']   = 'deposit';
            $return['transactions'][0]['amount'] = $entry['amount'];

            // destination is Spectre
            $return['transactions'][0]['destination_id'] = (int) $this->accounts[$spectreAccountId];

            // source is the other side:
            $return['transactions'][0]['source_name'] = $entry['extra']['payee'] ?? '(unknown source account)';

            $mappedId = $this->getMappedId($return['transactions'][0]['source_name']);
            if (null !== $mappedId && 0 !== $mappedId) {
                $mappedType                             = $this->getMappedType($mappedId);
                $return['transactions'][0]['type']      = $this->getTransactionType($mappedType, 'asset');
                $return['transactions'][0]['source_id'] = $mappedId;
                unset($return['transactions'][0]['source_name']);
            }
            //Log::debug(sprintf('Mapped ID is %s', var_export($mappedId, true)));
        }

        if (-1 === bccomp($entry['amount'], '0')) {
            // amount is negative: withdrawal or transfer.
            $return['transactions'][0]['amount'] = bcmul($entry['amount'], '-1');

            // source is Spectre:
            $return['transactions'][0]['source_id'] = (int) $this->accounts[$spectreAccountId];
            // dest is shop
            $return['transactions'][0]['destination_name'] = $entry['extra']['payee'] ?? '(unknown destination account)';

            $mappedId = $this->getMappedId($return['transactions'][0]['destination_name']);
            //Log::debug(sprintf('Mapped ID is %s', var_export($mappedId, true)));
            if (null !== $mappedId && 0 !== $mappedId) {
                $return['transactions'][0]['destination_id'] = $mappedId;
                $mappedType                                  = $this->getMappedType($mappedId);
                $return['transactions'][0]['type']           = $this->getTransactionType('asset', $mappedType);
                unset($return['transactions'][0]['destination_name']);
            }
        }
        app('log')->debug(sprintf('Parsed Spectre transaction #%d', $entry['id']));

        return $return;
    }

    /**
     * @param int $accountId
     *
     * @throws ApiHttpException
     * @return string
     */
    private function getAccountType(int $accountId): string
    {
        $uri   = (string) config('spectre.uri');
        $token = (string) config('spectre.access_token');
        app('log')->debug(sprintf('Going to download account #%d', $accountId));
        $request = new GetAccountRequest($uri, $token);
        $request->setId($accountId);
        /** @var GetAccountResponse $result */
        $result = $request->get();
        $type   = $result->getAccount()->type;

        app('log')->debug(sprintf('Discovered that account #%d is of type "%s"', $accountId, $type));

        return $type;
    }

    /**
     * @param string $name
     * @param string $iban
     *
     * @return int|null
     */
    private function getMappedId(string $name): ?int
    {
        if (isset($this->configuration->getMapping()[$name])) {
            return (int) $this->configuration->getMapping()['accounts'][$name];
        }

        return null;
    }

    /**
     * @param int $mappedId
     *
     * @return string
     */
    private function getMappedType(int $mappedId): string
    {
        if (!isset($this->configuration->getAccountTypes()[$mappedId])) {
            app('log')->warning(sprintf('Cannot find account type for Firefly III account #%d.', $mappedId));
            $accountType             = $this->getAccountType($mappedId);
            $accountTypes            = $this->configuration->getAccountTypes();
            $accountTypes[$mappedId] = $accountType;
            $this->configuration->setAccountTypes($accountTypes);

            return $accountType;
        }

        return $this->configuration->getAccountTypes()[$mappedId] ?? 'expense';
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @throws ImportException
     * @return string
     */
    private function getTransactionType(string $source, string $destination): string
    {
        $combination = sprintf('%s-%s', $source, $destination);
        switch ($combination) {
            default:
                throw new ImportException(sprintf('Unknown combination: %s and %s', $source, $destination));
            case 'asset-liabilities':
            case 'asset-expense':
                return 'withdrawal';
            case 'asset-asset':
                return 'transfer';
            case 'liabilities-asset':
            case 'revenue-asset':
                return 'deposit';
        }
    }
}
