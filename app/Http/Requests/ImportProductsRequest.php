<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            '*.name' => 'required|string',
            '*.sku' => 'nullable|string',
            '*.price' => 'required|numeric',
            '*.description' => 'nullable|string',
            '*.category' => 'nullable|string',
            '*.image_url' => 'nullable|url'
        ];
    }
}