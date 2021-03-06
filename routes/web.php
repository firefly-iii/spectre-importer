<?php
/**
 * web.php
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

use Illuminate\Support\Facades\Route;

Route::get('/', 'IndexController@index')->name('index');

// validate access token:
Route::get('/token', 'TokenController@index')->name('token.index');
Route::get('/token/validate', 'TokenController@doValidate')->name('token.validate');

// start import + config.
Route::get('/import/start', ['uses' => 'Import\StartController@index', 'as' => 'import.start']);
Route::post('/import/upload', ['uses' => 'Import\UploadController@upload', 'as' => 'import.upload']);

// list tokens (can be skipped)
Route::get('/import/spectre-connections', ['uses' => 'Import\ConnectionController@index', 'as' => 'import.connections.index']);
Route::post('/import/spectre-connections/submit', ['uses' => 'Import\ConnectionController@post', 'as' => 'import.connections.post']);

// once a connection has been made using a token (from previous step), pick up using callback:
Route::get('/import/callback', ['uses' => 'Import\CallbackController@index', 'as' => 'import.callback.index']);

// go to job configuration
Route::get('/import/configuration', ['uses' => 'Import\ConfigurationController@index', 'as' => 'import.configure.index']);
Route::post('/import/configuration', ['uses' => 'Import\ConfigurationController@post', 'as' => 'import.configure.post']);

// download from Spectre
Route::get('/import/download/index', ['uses' => 'Import\DownloadController@index', 'as' => 'import.download.index']);
Route::any('/import/download/start', ['uses' => 'Import\DownloadController@start', 'as' => 'import.download.start']);
Route::get('/import/download/status', ['uses' => 'Import\DownloadController@status', 'as' => 'import.download.status']);

// download config:
Route::get('/configuration/download', ['uses' => 'Import\ConfigurationController@download', 'as' => 'import.configuration.download']);

// map data:
Route::get('/import/mapping', ['uses' => 'Import\MappingController@index', 'as' => 'import.mapping.index']);
Route::post('/import/mapping', ['uses' => 'Import\MappingController@postIndex', 'as' => 'import.mapping.post']);

// send to Firefly III
Route::get('/import/sync', ['uses' => 'Import\SyncController@index', 'as' => 'import.sync.index']);
Route::any('/import/sync/start', ['uses' => 'Import\SyncController@start', 'as' => 'import.sync.start']);
Route::get('/import/sync/status', ['uses' => 'Import\SyncController@status', 'as' => 'import.sync.status']);

// clear session
Route::get('/flush','IndexController@flush')->name('flush');

// routes to go back to other steps (also takes care of session vars)
Route::get('/back/start', 'NavController@toStart')->name('back.start');
Route::get('/back/upload', 'NavController@toUpload')->name('back.upload');
Route::get('/back/connection', 'NavController@toConnection')->name('back.select-connection');
Route::get('/back/config', 'NavController@toConfig')->name('back.config');
Route::get('/back/mapping', 'NavController@toMapping')->name('back.mapping');
