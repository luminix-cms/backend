<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class ToDo extends Model
{
    use HasFactory, LuminixModel;

    protected $fillable = [
        'title',
        'description',
        'completed',
    ];

}
