<?php

namespace Luminix\Backend\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreatingResource
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Model $model
    ) {
    }

}
