<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'receiver' => 'required|string|max:20|min:20',
            'text'     => 'required|string',
            'id'       => 'required|integer|exists:messages,id',
        ];
    }
}
