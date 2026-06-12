<?php

namespace App\Http\Requests\Compte;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'libelle'       => ['required', 'string', 'min:3'],
            'numero'        => ['required', 'string', 'min:8', Rule::unique('comptes', 'numero')->where('user_id', Auth::id())],
            'solde_initial' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé du compte est requis',
            'libelle.string'   => 'Le libellé doit être une chaîne de caractères',
            'libelle.min'      => 'Le libellé doit contenir au moins 3 caractères',
            'numero.required'  => 'Le numéro de compte est requis',
            'numero.string'    => 'Le numéro de compte doit être une chaîne de caractères',
            'numero.min'       => 'Le numéro de compte doit contenir au moins 8 caractères',
            'numero.unique'       => 'Le numéro de compte doit être unique',
        ];
    }
}
