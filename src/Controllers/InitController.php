<?php

namespace Luminix\Backend\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Luminix\Backend\Macros;
use Luminix\Backend\Services\Manifest;

class InitController extends Controller
{
    public function init(Manifest $manifestService)
    {
        return response()->json($manifestService->makeBoot());
    }
}