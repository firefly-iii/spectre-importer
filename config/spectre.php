<?php
/**
 * spectre.php
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

$fireflyIIIURI = env('FIREFLY_III_URI');
if ('/' === substr($fireflyIIIURI, -1)) {
    $fireflyIIIURI = rtrim($fireflyIIIURI, '/');
}

return [
    'version'             => '2.0.0',
    'access_token'        => env('FIREFLY_III_ACCESS_TOKEN'),
    'uri'                 => $fireflyIIIURI,
    'vanity_uri'          => envNonEmpty('VANITY_URI'),
    'upload_path'         => storage_path('uploads'),
    'customer_identifier' => env('CUSTOMER_IDENTIFIER', 'default_ff3_customer'),
    'minimum_version'     => '5.4.3',
    'spectre_app_id'      => env('SPECTRE_APP_ID', ''),
    'spectre_secret'      => env('SPECTRE_SECRET', ''),
    'spectre_uri'         => 'https://www.saltedge.com/api/v5',
    'skip_key_step'       => false,
    'trusted_proxies'     => env('TRUSTED_PROXIES', ''),
    'connection'          => [
        'verify'  => env('VERIFY_TLS_SECURITY', true),
        'timeout' => (float)env('CONNECTION_TIMEOUT', 3.14),
    ],
];
