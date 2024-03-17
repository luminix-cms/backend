<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Luminix\Backend\Controllers\ResourceController;

class ToDoController extends ResourceController
{
    public function beforeSave(Request $request, $item)
    {
        if (!$item->user_id) {
            $item->user_id = auth()->id();
        }
    }
}