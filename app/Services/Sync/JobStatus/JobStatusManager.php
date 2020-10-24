<?php
/**
 * JobStatusManager.php
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

namespace App\Services\Sync\JobStatus;

use App\Services\Session\Constants;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Class JobStatusManager.
 */
class JobStatusManager
{
    /**
     * @param string $identifier
     * @param int    $index
     * @param string $error
     */
    public static function addError(string $identifier, int $index, string $error): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                   = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->errors[$index]   = $status->errors[$index] ?? [];
                $status->errors[$index][] = $error;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e);
        }
    }

    /**
     * @param string    $syncIdentifier
     * @param JobStatus $status
     */
    private static function storeJobStatus(string $syncIdentifier, JobStatus $status): void
    {
        app('log')->debug(sprintf('Now in Sync storeJobStatus(%s): %s', $syncIdentifier, $status->status));
        $array = $status->toArray();
        $disk  = Storage::disk('jobs');
        $disk->put($syncIdentifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        app('log')->debug('Done with storing.');
    }

    /**
     * @param string $identifier
     * @param int    $index
     * @param string $message
     */
    public static function addMessage(string $identifier, int $index, string $message): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->messages[$index]   = $status->messages[$index] ?? [];
                $status->messages[$index][] = $message;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e);
        }
    }

    /**
     * @param string $identifier
     * @param int    $index
     * @param string $warning
     */
    public static function addWarning(string $identifier, int $index, string $warning): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->warnings[$index]   = $status->warnings[$index] ?? [];
                $status->warnings[$index][] = $warning;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e);
        }
    }

    /**
     * @param string $status
     *
     * @return JobStatus
     */
    public static function setJobStatus(string $status): JobStatus
    {
        $syncIdentifier = session()->get(Constants::SYNC_JOB_IDENTIFIER);
        app('log')->debug(sprintf('Now in Sync setJobStatus(%s)', $status));
        app('log')->debug(sprintf('Found "%s" in the session', $syncIdentifier));

        $jobStatus         = self::startOrFindJob($syncIdentifier);
        $jobStatus->status = $status;

        self::storeJobStatus($syncIdentifier, $jobStatus);

        return $jobStatus;
    }

    /**
     * @param string $identifier
     *
     * @return JobStatus
     */
    public static function startOrFindJob(string $identifier): JobStatus
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $array = json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR);

                return JobStatus::fromArray($array);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error('Could not find sync file, write a new one.');
            app('log')->error($e->getMessage());
        }
        app('log')->debug('Sync file does not exist or error, create a new one.');
        $status = new JobStatus;
        $disk->put($identifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $status;
    }
}
