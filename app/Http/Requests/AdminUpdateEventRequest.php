<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Modification d'un événement par un ADMIN : règles d'UpdateEventRequest (date, heure, image, champs) sans le
 * contrôle « propriétaire », + promoteur (rôle promoteur obligatoire) et statut (draft, published, cancelled).
 * `published` n'est jamais appliqué sans passer par PublishEventAction (checklist).
 */
class AdminUpdateEventRequest extends UpdateEventRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return parent::rules() + [
            'promoter_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'promoteur')],
            'status' => ['required', 'in:draft,published,cancelled'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'promoter_id.required' => 'Choisissez le promoteur de cet événement.',
            'promoter_id.exists' => "Ce promoteur n'existe pas : choisissez un utilisateur de rôle promoteur.",
            'status.in' => 'Statut invalide (brouillon, publié ou annulé).',
        ];
    }
}
