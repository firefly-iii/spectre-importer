<?php
/**
 * TokenController.php
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
 * TokenController.php
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

namespace App\Http\Controllers;

use App\Services\Spectre\Request\ListCustomersRequest;
use App\Services\Spectre\Response\ErrorResponse;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\SystemInformationRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class TokenController
 */
class TokenController extends Controller
{
    /**
     * Check if the Firefly III API responds properly.
     *
     * @return JsonResponse
     */
    public function doValidate(): JsonResponse
    {
        $response = ['result' => 'OK', 'message' => null];
        $error    = $this->verifyFireflyIII();
        if (null !== $error) {
            // send user error:
            return response()->json(['result' => 'NOK', 'message' => $error]);
        }

        // Spectre:
        $error = $this->verifySpectre();
        if (null !== $error) {
            // send user error:
            return response()->json(['result' => 'NOK', 'message' => $error]);
        }

        return response()->json($response);
    }

    /**
     * @return string|null
     */
    private function verifyFireflyIII(): ?string
    {
        // verify access
        $uri     = (string)config('spectre.uri');
        $token   = (string)config('spectre.access_token');
        $request = new SystemInformationRequest($uri, $token);

        $request->setVerify(config('spectre.connection.verify'));
        $request->setTimeOut(config('spectre.connection.timeout'));

        try {
            $result = $request->get();
        } catch (ApiHttpException $e) {
            return $e->getMessage();
        }
        // -1 = OK (minimum is smaller)
        // 0 = OK (same version)
        // 1 = NOK (too low a version)

        // verify version:
        $minimum = (string)config('spectre.minimum_version');
        $compare = version_compare($minimum, $result->version);
        if (1 === $compare) {
            return sprintf(
                'Your Firefly III version %s is below the minimum required version %s',
                $result->version, $minimum
            );
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function verifySpectre(): ?string
    {
        $uri     = config('spectre.spectre_uri');
        $appId   = config('spectre.spectre_app_id');
        $secret  = config('spectre.spectre_secret');
        $request = new ListCustomersRequest($uri, $appId, $secret);

        $request->setTimeOut(config('spectre.connection.timeout'));

        $response = $request->get();
        if ($response instanceof ErrorResponse) {
            return sprintf('%s: %s', $response->class, $response->message);
        }

        return null;
    }

    /**
     * Same thing but not over JSON.
     *
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function index()
    {
        $pageTitle = 'Token error';

        // verify Firefly III
        $errorMessage = $this->verifyFireflyIII();
        if (null !== $errorMessage) {
            return view('token.index', compact('errorMessage', 'pageTitle'));
        }

        // verify Spectre:
        $errorMessage = $this->verifySpectre();

        if (null !== $errorMessage) {
            return view('token.index', compact('errorMessage', 'pageTitle'));
        }

        return redirect(route('index'));
    }

}
