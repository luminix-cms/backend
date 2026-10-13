<?php

namespace Luminix\Backend\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;

/**
 * A client asked for a `where` filter or an `order_by` the model doesn't
 * expose. It is invalid input, so it answers 422 and stays out of the error
 * log; with `app.debug` on, the response carries the detailed message.
 */
class InvalidFilterException extends Exception implements ShouldntReport
{
    public function __construct($message = 'Invalid filter provided.', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => config('app.debug')
                ? $this->getMessage()
                : __('luminix-backend::backend.invalid_filter'),
        ], 422);
    }
}
