<?php
/**
 * NavController.php
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

namespace App\Http\Controllers;


use App\Services\Session\Constants;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

/**
 * Class NavController
 */
class NavController extends Controller
{
    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function toConfig()
    {
        session()->forget(Constants::CONFIG_COMPLETE_INDICATOR);

        return redirect(sprintf('%s?overruleskip=true', route('import.configure.index')));
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function toConnection()
    {
        session()->forget(Constants::CONNECTION_SELECTED_INDICATOR);

        return redirect(route('import.connections.index'));
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function toMapping()
    {
        return redirect(route('import.mapping.index'));
    }

    /**
     * Redirect to index. Requires no special steps or middleware.
     */
    public function toStart()
    {
        return redirect(route('index'));

    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function toUpload()
    {
        session()->forget(Constants::HAS_UPLOAD);

        return redirect(route('import.start'));
    }

}
