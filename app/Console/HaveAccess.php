<?php
/**
 * HaveAccess.php
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

namespace App\Console;

use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\SystemInformationRequest;

/**
 * Trait HaveAccess.
 */
trait HaveAccess
{
    /**
     * @return bool
     */
    private function haveAccess(): bool
    {
        $url     = (string)config('spectre.url');
        $token   = (string)config('spectre.access_token');
        $request = new SystemInformationRequest($url, $token);

        $request->setVerify(config('spectre.connection.verify'));
        $request->setTimeOut(config('spectre.connection.timeout'));

        try {
            $request->get();
        } catch (ApiHttpException $e) {
            $this->error(sprintf('Could not connect to Firefly III: %s', $e->getMessage()));

            return false;
        }

        return true;
    }
}
