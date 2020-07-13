<?php
/**
 * Configuration.php
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
/**
 * Configuration.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii/csv-importer).
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

namespace App\Services\Configuration;

use Carbon\Carbon;
use Log;

/**
 * Class Configuration
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    private bool    $addImportTag;
    private bool    $ignoreDuplicateTransactions;
    private array   $mapping;
    private bool    $rules;
    private bool    $skipForm;
    private int     $version;
    private ?string $dateNotAfter;
    private ?string $dateNotBefore;
    private ?int    $dateRangeNumber;
    private ?string $dateRangeUnit;
    private ?string $dateRange;
    private array   $accounts;
    private string     $connection;
    private string     $identifier;
    private bool    $doMapping;
    private array   $accountTypes;
    private bool    $ignoreSpectreCategories;

    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->ignoreDuplicateTransactions = true;
        $this->rules                       = true;
        $this->skipForm                    = false;
        $this->addImportTag                = true;
        $this->ignoreSpectreCategories     = false;
        $this->mapping                     = [
            'accounts'   => [],
            'categories' => [],
        ];
        $this->doMapping                   = false;
        $this->version                     = self::VERSION;
        $this->identifier                  = '0';
        $this->connection                  = '0';
        $this->accounts                    = [];
        $this->dateRange                   = 'all';
        $this->dateRangeNumber             = 30;
        $this->dateRangeUnit               = 'd';
        $this->dateNotBefore               = '';
        $this->dateNotAfter                = '';
        $this->accountTypes                = [];
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        //Log::debug('Configuration::fromArray', $array);
        $version                             = $array['version'] ?? 1;
        $object                              = new self;
        $object->ignoreDuplicateTransactions = $array['ignore_duplicate_transactions'] ?? true;
        $object->rules                       = $array['rules'] ?? true;
        $object->skipForm                    = $array['skip_form'] ?? false;
        $object->addImportTag                = $array['add_import_tag'] ?? true;
        $object->ignoreSpectreCategories     = $array['ignore_spectre_categories'] ?? false;
        $object->mapping                     = $array['mapping'] ?? ['accounts' => [], 'categories' => []];
        $object->doMapping                   = $array['do_mapping'] ?? false;
        $object->identifier                  = (string)($array['identifier'] ?? '0');
        $object->connection                  = (string)($array['connection'] ?? '0');
        $object->accounts                    = $array['accounts'] ?? [];
        $object->dateRange                   = $array['date_range'] ?? 'all';
        $object->dateRangeNumber             = $array['date_range_number'] ?? 30;
        $object->dateRangeUnit               = $array['date_range_unit'] ?? 'd';
        $object->dateNotBefore               = $array['date_not_before'] ?? '';
        $object->dateNotAfter                = $array['date_not_after'] ?? '';
        $object->accountTypes                = $array['account_types'] ?? [];
        $object->version                     = $version;
        $object->updateDateRange();

        return $object;
    }

    /**
     * @return array
     */
    public function getAccountTypes(): array
    {
        return $this->accountTypes;
    }

    /**
     * @return bool
     */
    public function emptyMapping(): bool
    {
        return $this->mapping === ['accounts' => [], 'categories' => []];
    }

    /**
     * @return string
     */
    public function getDateRange(): string
    {
        return $this->dateRange;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public static function fromFile(array $data): self
    {
        Log::debug('Now in Configuration::fromFile', $data);
        $version = $data['version'] ?? 1;

        return self::fromArray($data);
    }

    /**
     * @return bool
     */
    public function isDoMapping(): bool
    {
        return $this->doMapping;
    }


    /**
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping ?? [];
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping): void
    {
        $this->mapping = $mapping;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles ?? [];
    }

    /**
     * @return bool
     */
    public function isAddImportTag(): bool
    {
        return $this->addImportTag;
    }


    /**
     * @return bool
     */
    public function isIgnoreDuplicateTransactions(): bool
    {
        return $this->ignoreDuplicateTransactions;
    }

    /**
     * @return bool
     */
    public function isRules(): bool
    {
        return $this->rules;
    }


    /**
     * @return bool
     */
    public function isSkipForm(): bool
    {
        return $this->skipForm;
    }

    /**
     * @return string
     */
    public function getDateNotAfter(): string
    {
        return $this->dateNotAfter;
    }

    /**
     * @return string
     */
    public function getDateNotBefore(): string
    {
        return $this->dateNotBefore;
    }

    /**
     * @return int
     */
    public function getDateRangeNumber(): int
    {
        return $this->dateRangeNumber;
    }

    /**
     * @return string
     */
    public function getDateRangeUnit(): string
    {
        return $this->dateRangeUnit;
    }


    /**
     * @param array $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function isIgnoreSpectreCategories(): bool
    {
        return $this->ignoreSpectreCategories;
    }

    /**
     * @param bool $ignoreSpectreCategories
     */
    public function setIgnoreSpectreCategories(bool $ignoreSpectreCategories): void
    {
        $this->ignoreSpectreCategories = $ignoreSpectreCategories;
    }

    /**
     * @return array
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * @param bool $addImportTag
     */
    public function setAddImportTag(bool $addImportTag): void
    {
        $this->addImportTag = $addImportTag;
    }

    /**
     * @param bool $ignoreDuplicateTransactions
     */
    public function setIgnoreDuplicateTransactions(bool $ignoreDuplicateTransactions): void
    {
        $this->ignoreDuplicateTransactions = $ignoreDuplicateTransactions;
    }

    /**
     * @param bool $rules
     */
    public function setRules(bool $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * @param bool $skipForm
     */
    public function setSkipForm(bool $skipForm): void
    {
        $this->skipForm = $skipForm;
    }

    /**
     * @param string $dateNotAfter
     */
    public function setDateNotAfter(string $dateNotAfter): void
    {
        $this->dateNotAfter = $dateNotAfter;
    }

    /**
     * @param array $accountTypes
     */
    public function setAccountTypes(array $accountTypes): void
    {
        $this->accountTypes = $accountTypes;
    }

    /**
     * @param string $dateNotBefore
     */
    public function setDateNotBefore(string $dateNotBefore): void
    {
        $this->dateNotBefore = $dateNotBefore;
    }

    /**
     * @param int $dateRangeNumber
     */
    public function setDateRangeNumber(int $dateRangeNumber): void
    {
        $this->dateRangeNumber = $dateRangeNumber;
    }

    /**
     * @param string $dateRangeUnit
     */
    public function setDateRangeUnit(string $dateRangeUnit): void
    {
        $this->dateRangeUnit = $dateRangeUnit;
    }

    /**
     * @param string $dateRange
     */
    public function setDateRange(string $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    /**
     * @param bool $doMapping
     */
    public function setDoMapping(bool $doMapping): void
    {
        $this->doMapping = $doMapping;
    }

    /**
     * @param array $accounts
     */
    public function setAccounts(array $accounts): void
    {
        Log::debug('Configuration::setAccounts', $accounts);
        $this->accounts = $accounts;
    }

    /**
     *
     */
    public function updateDateRange(): void
    {
        Log::debug('Now in updateDateRange()');
        // set date and time:
        switch ($this->dateRange) {
            case 'all':
                Log::debug('Range is null, set all to NULL.');
                $this->dateRangeUnit   = 'd';
                $this->dateRangeNumber = 30;
                $this->dateNotBefore   = '';
                $this->dateNotAfter    = '';
                break;
            case 'partial':
                Log::debug('Range is partial, after is NULL, dateNotBefore will be calculated.');
                $this->dateNotAfter  = '';
                $this->dateNotBefore = self::calcDateNotBefore($this->dateRangeUnit, $this->dateRangeNumber);
                Log::debug(sprintf('dateNotBefore is now "%s"', $this->dateNotBefore));
                break;
            case 'range':
                Log::debug('Range is "range", both will be created from a string.');
                $before = $this->dateNotBefore; // string
                $after  = $this->dateNotAfter; // string
                if (null !== $before) {
                    $before = Carbon::createFromFormat('Y-m-d', $before);
                }
                if (null !== $after) {
                    $after = Carbon::createFromFormat('Y-m-d', $after);
                }

                if (null !== $before && null !== $after && $before > $after) {
                    [$before, $after] = [$after, $before];
                }

                $this->dateNotBefore = null === $before ? '' : $before->format('Y-m-d');
                $this->dateNotAfter  = null === $after ? '' : $after->format('Y-m-d');
                Log::debug(sprintf('dateNotBefore is now "%s", dateNotAfter is "%s"', $this->dateNotBefore, $this->dateNotAfter));
        }
    }


    /**
     * @param string $unit
     * @param int    $number
     *
     * @return string|null
     */
    private static function calcDateNotBefore(string $unit, int $number): ?string
    {
        $functions = [
            'd' => 'subDays',
            'w' => 'subWeeks',
            'm' => 'subMonths',
            'y' => 'subYears',
        ];
        if (isset($functions[$unit])) {
            $today    = Carbon::now();
            $function = $functions[$unit];
            $today->$function($number);

            return $today->format('Y-m-d');
        }
        app('log')->error(sprintf('Could not parse date setting. Unknown key "%s"', $unit));

        return null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'ignore_duplicate_transactions' => $this->ignoreDuplicateTransactions,
            'rules'                         => $this->rules,
            'skip_form'                     => $this->skipForm,
            'add_import_tag'                => $this->addImportTag,
            'do_mapping'                    => $this->doMapping,
            'mapping'                       => $this->mapping,
            'identifier'                    => (string) $this->identifier,
            'connection'                    => (string) $this->connection,
            'version'                       => $this->version,
            'accounts'                      => $this->accounts,
            'date_range'                    => $this->dateRange,
            'date_range_number'             => $this->dateRangeNumber,
            'date_range_unit'               => $this->dateRangeUnit,
            'date_not_before'               => $this->dateNotBefore,
            'date_not_after'                => $this->dateNotAfter,
            'account_types'                 => $this->accountTypes,
            'ignore_spectre_categories'     => $this->ignoreSpectreCategories,
        ];

        return $array;
    }


}
