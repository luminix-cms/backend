<?php

namespace Luminix\Backend\Model;

use Illuminate\Http\Request;

trait ValidatesRequests
{
    /**
     * Get the validation rules for the model.
     *
     * @param 'store'|'update' $for
     * @return array
     */
    public function getValidationRules(string $for): array
    {
        return [];
    }

    public function validateRequest(Request $request, string $for)
    {
        if (empty($this->getValidationRules($for))) {
            return;
        }
        $request->validate($this->getValidationRules($for));
    }
}
