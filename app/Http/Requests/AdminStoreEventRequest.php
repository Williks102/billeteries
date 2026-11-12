<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Création d'un événement par un ADMIN pour un promoteur choisi.
 * Mêmes règles que StoreEventRequest + `promoter_id` : obligatoire, et doit être un utilisateur de rôle promoteur
 * (jamais un acheteur, un admin ou un identifiant inexistant). Le middleware `admin` protège déjà la route ;
 * authorize() le re-vérifie (défense en profondeur).
 */
class AdminStoreEventRequest extends StoreEventRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return parent::rules() + [
            'promoter_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'promoteur')],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'promoter_id.required' => 'Choisissez le promoteur de cet événement.',
            'promoter_id.integer' => "Ce promoteur n'est pas valide.",
            'promoter_id.exists' => "Ce promoteur n'existe pas : choisissez un utilisateur de rôle promoteur.",
        ];
    }

    /** Promoteur choisi (déjà validé comme utilisateur de rôle promoteur). */
    public function promoterId(): int
    {
        return (int) $this->validated()['promoter_id'];
    }
}
