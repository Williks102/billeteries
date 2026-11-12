<?php

namespace App\Http\Requests;

use App\Models\Event;

/**
 * Mise à jour d'un événement par son promoteur. Même jeu de règles que la création, sauf la date :
 * un événement passé déjà existant reste modifiable tant que sa date n'est pas changée.
 */
class UpdateEventRequest extends EventFormRequest
{
    public function authorize(): bool
    {
        $event = $this->route()?->parameter('event');

        return $event instanceof Event && $this->user() !== null && (int) $event->promoter_id === (int) $this->user()->id;
    }

    protected function eventDateRules(): array
    {
        $event = $this->route()?->parameter('event');
        $current = $event instanceof Event && $event->event_date ? $event->event_date->format('Y-m-d') : null;

        $rules = ['required', 'date_format:Y-m-d'];
        if ($this->input('event_date') !== $current) {
            $rules[] = 'after_or_equal:today';
        }

        return $rules;
    }

    /** Champs à mettre à jour : seulement ceux présents dans la requête (le formulaire d'édition n'envoie pas les conditions). */
    public function eventData(): array
    {
        $data = parent::eventData();
        foreach (['description', 'terms_conditions'] as $optional) {
            if (! $this->has($optional)) {
                unset($data[$optional]);
            }
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = (string) $data['description'];
        }

        return $data;
    }
}
