<?php
/**
 * ConfigurationPostRequest.php
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

namespace App\Http\Requests;


/**
 * Class ConfigurationPostRequest
 */
class ConfigurationPostRequest extends Request
{
    /**
     * @return array
     */
    public function getAll(): array
    {
        // parse entire config file.
        $doImport = $this->get('do_import') ?? [];

        return [
            'do_import'                 => $doImport,
            'rules'                     => $this->convertBoolean($this->get('rules')),
            'skip_form'                 => $this->convertBoolean($this->get('skip_form')),
            'ignore_spectre_categories' => $this->convertBoolean($this->get('ignore_spectre_categories')),
            'date_range'                => $this->string('date_range'),
            'date_range_number'         => $this->integer('date_range_number'),
            'date_range_unit'           => $this->string('date_range_unit'),
            'date_not_before'           => $this->date('date_not_before'),
            'date_not_after'            => $this->date('date_not_after'),
            'do_mapping'                => $this->convertBoolean($this->get('do_mapping')),
            'accounts'                  => $this->get('accounts'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            //'some_weird_field' => 'required',
            'rules'             => 'numeric|between:0,1',
            'do_mapping'        => 'numeric|between:0,1',
            'date_range'        => 'required|in:all,partial,range',
            'date_range_number' => 'numeric|between:1,365',
            'date_range_unit'   => 'required|in:d,w,m,y',
            'date_not_before'   => 'date|nullable',
            'date_not_after'    => 'date|nullable',
            'accounts.*'        => 'numeric',
            'do_import.*'       => 'numeric',
        ];
    }

}
