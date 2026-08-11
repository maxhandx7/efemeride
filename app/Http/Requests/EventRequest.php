<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_column(EventType::cases(), 'value'))],
            'day' => ['required', 'integer', 'between:1,31'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:1900,'.date('Y')],
            'chat_id' => ['nullable', 'string', 'max:60'],
            'template' => ['nullable', 'string', 'max:1000'],
            'use_ai' => ['boolean'],
            'send_at' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'days_before' => ['array'],
            'days_before.*' => ['integer', 'between:0,365'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'use_ai' => $this->boolean('use_ai'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe de quien es la fecha.',
            'day.between' => 'Ese dia no existe en ningun calendario conocido.',
            'send_at.date_format' => 'La hora va en formato 24h, por ejemplo 08:00.',
        ];
    }
}
