<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'categorie_id' => [
                'required',
                Rule::exists('categories', 'id')->where('user_id', Auth::id()),
            ],
            'montant'     => 'required|numeric|min:0',
            'type'        => 'required|in:depense,revenu',
            'description' => 'nullable|string|max:500',
            'date'        => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'La catégorie est requise',
            'categorie_id.exists'   => 'La catégorie sélectionnée est invalide ou ne vous appartient pas',
            'montant.required'      => 'Le montant est requis',
            'type.required'         => 'Le type est requis',
            'type.in'               => 'Le type doit être "depense" ou "revenu"',
            'date.required'         => 'La date est requise',
            'date.date'             => 'La date doit être une date valide',
        ];
    }
}
