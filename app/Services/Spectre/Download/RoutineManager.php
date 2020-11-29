<?php
/**
 * RoutineManager.php
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

namespace App\Services\Spectre\Download;

use App\Services\Configuration\Configuration;
use Storage;
use Str;

/**
 * Class RoutineManager
 */
class RoutineManager
{
    /** @var string */
    private const DISKNAME = 'downloads';
    private array  $allErrors;
    private array  $allMessages;
    private array  $allWarnings;
    private Configuration        $configuration;
    private string $downloadIdentifier;
    private TransactionProcessor $transactionProcessor;

    /**
     * RoutineManager constructor.
     *
     * @param string|null $downloadIdentifier
     */
    public function __construct(string $downloadIdentifier = null)
    {
        app('log')->debug('Constructed Spectre download routine manager.');

        // get line converter
        $this->allMessages          = [];
        $this->allWarnings          = [];
        $this->allErrors            = [];
        $this->transactionProcessor = new TransactionProcessor;
        if (null === $downloadIdentifier) {
            app('log')->debug('Was given no download identifier, will generate one.');
            $this->generateDownloadIdentifier();
        }
        if (null !== $downloadIdentifier) {
            app('log')->debug('Was given download identifier, will use it.');
            $this->downloadIdentifier = $downloadIdentifier;
        }
    }

    /**
     *
     */
    private function generateDownloadIdentifier(): void
    {
        app('log')->debug('Going to generate download identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $downloadIdentifier = Str::random(16);
            $count++;
            app('log')->debug(sprintf('Attempt #%d results in "%s"', $count, $downloadIdentifier));
        } while ($count < 30 && $disk->exists($downloadIdentifier));
        $this->downloadIdentifier = $downloadIdentifier;
        app('log')->info(sprintf('Download job identifier is "%s"', $downloadIdentifier));
    }

    /**
     * @return array
     */
    public function getAllErrors(): array
    {
        return $this->allErrors;
    }

    /**
     * @return array
     */
    public function getAllMessages(): array
    {
        return $this->allMessages;
    }

    /**
     * @return array
     */
    public function getAllWarnings(): array
    {
        return $this->allWarnings;
    }

    /**
     * @return string
     */
    public function getDownloadIdentifier(): string
    {
        return $this->downloadIdentifier;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->transactionProcessor->setConfiguration($configuration);
        $this->transactionProcessor->setDownloadIdentifier($this->downloadIdentifier);
    }

    /**
     *
     */
    public function start(): void
    {

        // get transactions from Spectre
        $transactions = $this->transactionProcessor->download();

        // store on drive in downloadIdentifier.
        $disk = Storage::disk(self::DISKNAME);
        $disk->put($this->downloadIdentifier, json_encode($transactions, JSON_THROW_ON_ERROR, 512));
    }


}
