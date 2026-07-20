<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.mimes' => 'Le format doit être JPG, PNG, WEBP ou GIF.',
            'avatar.max' => 'La photo de profil ne doit pas dépasser 2 Mo.',
        ];
    }
}
