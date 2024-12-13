<?php

namespace Luminix\Backend\Model;

use Illuminate\Http\Request;
use Luminix\Backend\Validation\ValidatedBy;

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
        $reflection = new \ReflectionClass($this);
        $validatorAttribute = $reflection->getAttributes(ValidatedBy::class)[0] ?? null;
        if (!$validatorAttribute) {
            return [];
        }
        $validator = $validatorAttribute->newInstance()->getValidator();
        return $validator->getValidationRules($for, $this);
    }

    public function validateRequest(Request $request, string $for)
    {
        if (empty($this->getValidationRules($for))) {
            return;
        }
        $request->validate($this->getValidationRules($for));
    }
}
