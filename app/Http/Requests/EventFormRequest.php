<?php

namespace App\Http\Requests;

use App\Support\Events\SalePeriod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Règles communes de création / mise à jour d'un événement (source unique des règles et des messages).
 * Le promoteur de l'événement n'est JAMAIS lu dans la requête : il vient de l'utilisateur connecté
 * (ou, côté admin, d'un identifiant passé explicitement à l'Action après contrôle).
 */
abstract class EventFormRequest extends FormRequest
{
    /** Champs de l'événement acceptés (liste explicite : jamais la requête entière). */
    public const EVENT_FIELDS = [
        'title', 'category_id', 'description', 'venue', 'address',
        'event_date', 'event_time', 'end_time', 'terms_conditions',
    ];

    /** Règle de la date de l'événement (création : >= aujourd'hui ; mise à jour : voir UpdateEventRequest). */
    abstract protected function eventDateRules(): array;

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['event_time', 'end_time'] as $key) {
            $value = $this->input($key);
            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                $merge[$key] = substr($value, 0, 5);
            }
        }

        // address est NOT NULL en base : par défaut, le lieu.
        if (trim((string) $this->input('address')) === '') {
            $merge['address'] = $this->input('venue');
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:event_categories,id'],
            'event_date' => $this->eventDateRules(),
            'event_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:event_time'],
            'venue' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => "Indiquez le titre de l'événement.",
            'category_id.required' => 'Choisissez une catégorie.',
            'category_id.exists' => "Cette catégorie n'existe pas.",
            'event_date.required' => "Indiquez la date de l'événement.",
            'event_date.date_format' => "La date de l'événement n'est pas valide.",
            'event_date.after_or_equal' => "La date de l'événement ne peut pas être dans le passé.",
            'event_time.required' => "Indiquez l'heure de début.",
            'event_time.date_format' => "L'heure de début doit être au format HH:MM.",
            'end_time.date_format' => "L'heure de fin doit être au format HH:MM.",
            'end_time.after' => "L'heure de fin doit être après l'heure de début.",
            'venue.required' => 'Indiquez le lieu.',
            'address.required' => "Indiquez l'adresse.",
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => "L'image doit être au format JPEG, PNG ou GIF.",
            'image.max' => "L'image ne doit pas dépasser 2 Mo.",
            'image.uploaded' => "L'image n'a pas pu être envoyée (2 Mo maximum).",
        ];
    }

    /** Champs de l'événement validés (liste explicite). */
    public function eventData(): array
    {
        $data = array_intersect_key($this->validated(), array_flip(self::EVENT_FIELDS));
        if ($this instanceof StoreEventRequest) {
            $data['description'] = (string) ($data['description'] ?? '');
        }

        return $data;
    }

    /** Début de l'événement d'après la saisie, ou null si date/heure invalides. */
    protected function submittedEventStart(Validator $validator): ?\Carbon\Carbon
    {
        $errors = $validator->errors();
        if ($errors->has('event_date') || $errors->has('event_time')) {
            return null;
        }
        $date = (string) $this->input('event_date');
        $time = (string) $this->input('event_time');
        if ($date === '' || $time === '') {
            return null;
        }

        return SalePeriod::eventStart($date, $time);
    }
}
