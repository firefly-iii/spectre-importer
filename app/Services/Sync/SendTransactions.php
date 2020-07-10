<?php
/**
 * SendTransactions.php
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

use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\ProgressInformation;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Transaction;
use GrumpyDictator\FFIIIApiSupport\Model\TransactionGroup;
use GrumpyDictator\FFIIIApiSupport\Request\PostTagRequest;
use GrumpyDictator\FFIIIApiSupport\Request\PostTransactionRequest;
use GrumpyDictator\FFIIIApiSupport\Request\PutTransactionRequest;
use GrumpyDictator\FFIIIApiSupport\Response\PostTagResponse;
use GrumpyDictator\FFIIIApiSupport\Response\PostTransactionResponse;
use GrumpyDictator\FFIIIApiSupport\Response\ValidationErrorResponse;
use Log;

/**
 * Class SendTransactions.
 */
class SendTransactions
{
    use ProgressInformation;

    /** @var Configuration */
    private $configuration;

    private bool   $addTag;
    private string $tag;
    private string $tagDate;
    private string $rootURI;

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function send(array $transactions): array
    {
        // create the tag, to be used later on.
        $this->tag     = sprintf('Spectre Import on %s', date('Y-m-d \@ H:i'));
        $this->tagDate = date('Y-m-d');
        $this->createTag();

        $this->rootURI = config('spectre.uri');
        if ('' !== (string) config('spectre.vanity_uri')) {
            $this->rootURI = config('spectre.vanity_uri');
        }
        Log::debug(sprintf('The root URI is "%s"', $this->rootURI));

        $uri   = (string) config('spectre.uri');
        $token = (string) config('spectre.access_token');
        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('Trying to send transaction #%d', $index), $transaction);
            $group = $this->sendTransaction($uri, $token, $index, $transaction);
            if (null !== $group) {
                $this->addTagToGroup($group);
            }
        }

        return [];
    }

    /**
     * @param TransactionGroup $group
     */
    private function addTagToGroup(TransactionGroup $group): void
    {
        if (false === $this->addTag) {
            Log::debug('Will not add import tag.');
            return;
        }

        $groupId = (int) $group->id;
        Log::debug(sprintf('Going to add import tag to transaction group #%d', $groupId));
        $body = [
            'transactions' => [],
        ];
        /** @var Transaction $transaction */
        foreach ($group->transactions as $transaction) {
            /** @var array $currentTags */
            $currentTags = $transaction->tags;
            $currentTags[] = $this->tag;

            $body['transactions'][] = [
                'transaction_journal_id' => $transaction->id,
                'tags'                   => $currentTags,
            ];
        }
        $uri     = (string) config('spectre.uri');
        $token   = (string) config('spectre.access_token');
        $request = new PutTransactionRequest($uri, $token, $groupId);
        $request->setVerify(config('spectre.connection.verify'));
        $request->setTimeOut(config('spectre.connection.timeout'));
        $request->setBody($body);
        $request->put();

    }


    /**
     *
     */
    private function createTag(): void
    {
        if (false === $this->addTag) {
            Log::debug('Not instructed to add a tag, so will not create one.');

            return;
        }
        $uri     = (string) config('spectre.uri');
        $token   = (string) config('spectre.access_token');
        $request = new PostTagRequest($uri, $token);
        $request->setVerify(config('spectre.connection.verify'));
        $request->setTimeOut(config('spectre.connection.timeout'));
        $body = [
            'tag'  => $this->tag,
            'date' => $this->tagDate,
        ];
        $request->setBody($body);

        try {
            /** @var PostTagResponse $response */
            $response = $request->post();
        } catch (ApiHttpException $e) {
            Log::error(sprintf('Could not create tag. %s', $e->getMessage()));

            return;
        }
        if ($response instanceof ValidationErrorResponse) {
            Log::error($response->errors);

            return;
        }
        if (null !== $response->getTag()) {
            Log::info(sprintf('Created tag #%d "%s"', $response->getTag()->id, $response->getTag()->tag));
        }
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->addTag        = true;
        $this->configuration = $configuration;
        $this->addTag        = $configuration->isAddImportTag();
    }


    /**
     * @param string $uri
     * @param string $token
     * @param int    $index
     * @param array  $transaction
     *
     * @return TransactionGroup|null
     */
    private function sendTransaction(string $uri, string $token, int $index, array $transaction): ?TransactionGroup
    {
        $request = new PostTransactionRequest($uri, $token);

        $request->setVerify(config('spectre.connection.verify'));
        $request->setTimeOut(config('spectre.connection.timeout'));
        $request->setBody($transaction);
        try {
            /** @var PostTransactionResponse $response */
            $response = $request->post();
        } catch (ApiHttpException $e) {
            app('log')->error($e->getMessage());
            $this->addError($index, $e->getMessage());

            return null;
        }
        if ($response instanceof ValidationErrorResponse) {
            /** ValidationErrorResponse $error */
            foreach ($response->errors->getMessages() as $key => $errors) {
                foreach ($errors as $error) {
                    // +1 so the line numbers match.
                    $this->addError($index + 1, $error);
                    app('log')->error(sprintf('Could not create transaction: %s', $error), $transaction);
                }
            }

            return null;
        }
        /** @var TransactionGroup|null $group */
        $group = $response->getTransactionGroup();
        if (null === $group) {
            $this->addError($index + 1, 'Group is unexpectedly NULL.');

            return null;
        }
        $groupId  = $group->id;
        $groupUri = (string) sprintf('%s/transactions/show/%d', $this->rootURI, $groupId);

        /** @var Transaction $tr */
        foreach ($group->transactions as $tr) {
            $this->addMessage(
                $index + 1,
                sprintf(
                    'Created transaction #%d: <a href="%s">%s</a> (%s %s)', $groupId, $groupUri, $tr->description, $tr->currencyCode,
                    round((float) $tr->amount, 2)
                )
            );
        }

        return $group;
    }
}
