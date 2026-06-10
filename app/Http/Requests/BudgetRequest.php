<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BudgetRequest extends FormRequest
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
            "libelle" => "required|string|max:255",
            "plafond" => "required|numeric|min:0.01",
        ];
    }

    public function messages() : array {
        return [
            "libelle.required" => "Le libellé est requis",
            "libelle.string" => "Le libellé doit être une chaîne de caractères",
            "libelle.max" => "Le libellé doit contenir au maximum 255 caractères",
            "plafond.required" => "Le plafond est requis",
            "plafond.numeric" => "Le plafond doit être un nombre",
            "plafond.min" => "Le plafond doit être supérieur à 0",
        ];
    }

    public function attributes() : array {
        return [
            "libelle" => "libellé",
            "plafond" => "plafond",
        ];
    }
}
