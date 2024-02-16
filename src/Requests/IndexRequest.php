<?php

namespace Luminix\Backend\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool 
     */
    public function authorize()
    {
        return true;
    }

    public function prepareForValidation()
    {
        if ($this->has('filters')) {
            $this->merge([
                'filters' => json_decode($this->filters, true),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string,string> 
     */
    public function rules()
    {
        return [
            'page' => 'integer',
            'per_page' => 'integer|max:' . config('luminix.routing.max_per_page', '150'),
            'order_by' => 'string',
            'q' => 'string',
            'filters' => 'array',
            'tab' => 'string',
            'minified' => 'boolean',
        ];
    }
}
