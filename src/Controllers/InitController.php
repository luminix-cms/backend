<?php

namespace Luminix\Backend\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Luminix\Backend\Services\Manifest;

class InitController extends Controller
{
    public function init(Manifest $manifestService)
    {
        $response = [
            'data' => [
                'user' => auth()->user(),
            ],
        ];

        if (Config::get('luminix.boot.includes_manifest_data', true)) {
            $response += [
                'models' => $manifestService->models(),
                'routes' => $manifestService->routes(),
            ];
        }
        return response()->json($response);
    }
}