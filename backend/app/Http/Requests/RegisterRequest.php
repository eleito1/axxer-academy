<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['bail', 'required', 'string', Password::min(8), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'interested_product_id' => ['required', 'exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'company.required' => 'A empresa é obrigatória.',
            'city.required' => 'A cidade é obrigatória.',
            'whatsapp.required' => 'O WhatsApp é obrigatório.',
            'whatsapp.string' => 'Informe um número de WhatsApp válido.',
            'whatsapp.max' => 'Informe um número de WhatsApp válido.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password_confirmation.required' => 'A confirmação da senha é obrigatória.',
            'interested_product_id.required' => 'O produto de interesse é obrigatório.',
            'interested_product_id.exists' => 'Selecione um produto de interesse válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'company' => 'empresa',
            'city' => 'cidade',
            'whatsapp' => 'WhatsApp',
            'email' => 'e-mail',
            'password' => 'senha',
            'password_confirmation' => 'confirmação da senha',
            'interested_product_id' => 'produto de interesse',
        ];
    }
}
