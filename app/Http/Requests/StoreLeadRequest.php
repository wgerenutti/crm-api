<?php

namespace App\Http\Requests;

use App\Services\CrmApiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // As origens são cadastradas pelo marketing no CRM central
        $sources = (new CrmApiClient())->getSources();

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => ['required', Rule::in($sources)],
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
