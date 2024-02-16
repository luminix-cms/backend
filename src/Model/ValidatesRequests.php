<?php

namespace Luminix\Backend\Model;

trait ValidatesRequests
{
    /**
     * Get the validation rules for the model.
     *
     * @param 'store'|'update' $for
     * @return array
     */
    public function getValidationRules($for)
    {
        return [];
    }

    public function validateRequest($request, $for)
    {
        if (empty($this->getValidationRules($for))) {
            return $request->all();
        }
        return $request->validate($this->getValidationRules($for));
    }
}
