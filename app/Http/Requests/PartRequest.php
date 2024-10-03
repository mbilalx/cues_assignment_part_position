<?php

namespace App\Http\Requests;

use App\Models\Episode;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Foundation\Http\FormRequest;

class PartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => [
                'episode_id' => ['required', 'integer', new Exists(Episode::class, 'id')],
                'title' => ['required', 'string'],
                'position' => ['nullable', 'integer'],
            ],
            'PUT' => [
                'title' => ['required', 'string'],
                'position' => ['required', 'integer'],
            ],
            'PATCH' => [
                'position' => ['required', 'integer'],
            ]
        };
    }
}
