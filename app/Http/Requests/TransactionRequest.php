<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant'      => ['required', 'numeric', 'min:0.01'],
            'date'         => ['required', 'date'],
            'description'  => ['nullable', 'string', 'max:255'],
            'type'         => ['required', 'in:depense,revenu'],
            'categorie_id' => ['required', Rule::exists('categories', 'id')->where('user_id', Auth::id())],
            'compte_id'    => ['required', Rule::exists('comptes', 'id')->where('user_id', Auth::id())],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.required'      => 'Le montant est requis',
            'montant.numeric'       => 'Le montant doit être un nombre',
            'montant.min'           => 'Le montant doit être supérieur à 0',
            'date.required'         => 'La date est requise',
            'date.date'             => 'La date n\'est pas valide',
            'type.required'         => 'Le type est requis',
            'type.in'               => 'Le type doit être "depense" ou "revenu"',
            'categorie_id.required' => 'La catégorie est requise',
            'categorie_id.exists'   => 'La catégorie sélectionnée est invalide',
            'compte_id.required'    => 'Le compte est requis',
            'compte_id.exists'      => 'Le compte sélectionné est invalide',
        ];
    }
}
