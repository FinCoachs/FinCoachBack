<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categorie_id' => 'required|exists:categories,id',
            'montant' => 'required|numeric|min:0',
            'type' => 'required|in:depense,revenu',
            'description' => 'required|string',
            'date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'La catégorie est requise',
            'montant.required' => 'Le montant est requis',
            'type.required' => 'Le type est requis',
            'description.required' => 'La description est requise',
            'date.required' => 'La date est requise',
            'date.date' => 'La date doit être une date valide',
        ];
    }
}
