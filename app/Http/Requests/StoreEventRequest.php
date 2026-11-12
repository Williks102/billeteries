<?php

namespace App\Http\Requests;

use App\Support\Events\EventFormDefaults;
use App\Support\Events\SalePeriod;
use Illuminate\Contracts\Validation\Validator;

/**
 * Création d'un événement + de ses billets en une seule requête.
 * Champs obligatoires pour un brouillon : titre, catégorie, date, heure de début, lieu. Billets : 0 à N.
 */
class StoreEventRequest extends EventFormRequest
{
    /** Nom du billet pré-rempli par le formulaire : un bloc intact (sans prix) n'est pas un billet. */
    public const DEFAULT_TICKET_NAME = 'Standard';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function eventDateRules(): array
    {
        return ['required', 'date_format:Y-m-d', 'after_or_equal:today'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Bloc « Standard » laissé INTACT (sans prix, quantité / max par défaut, ni description ni dates) : ignoré,
        // ce n'est pas un billet. Modifié sans prix : erreur de validation sur le prix.
        // Les clés sont conservées pour que les erreurs retrouvent le bon bloc du formulaire.
        $tickets = $this->input('ticket_types');
        if (is_array($tickets)) {
            foreach ($tickets as $i => $row) {
                if (is_array($row) && $this->isUntouchedBlock($row)) {
                    unset($tickets[$i]);
                }
            }
            $this->merge(['ticket_types' => $tickets]);
        }
    }

    private function isUntouchedBlock(array $row): bool
    {
        $blank = fn (string $k) => trim((string) ($row[$k] ?? '')) === '';
        $same = fn (string $k, int $default) => $blank($k) || trim((string) $row[$k]) === (string) $default;
        $name = trim((string) ($row['name'] ?? ''));

        return $blank('price') && ($name === '' || $name === self::DEFAULT_TICKET_NAME)
            && $same('quantity_available', EventFormDefaults::QUANTITY)
            && $same('max_per_order', EventFormDefaults::MAX_PER_ORDER)
            && $blank('description') && $blank('sale_start_date') && $blank('sale_end_date');
    }

    public function rules(): array
    {
        return parent::rules() + [
            'action' => ['nullable', 'in:draft,publish'],
            'ticket_types' => ['nullable', 'array', 'max:20'],
            'ticket_types.*' => ['array'],
            'ticket_types.*.name' => ['required', 'string', 'max:255'],
            'ticket_types.*.description' => ['nullable', 'string', 'max:500'],
            'ticket_types.*.price' => ['required', 'integer', 'min:0'],
            'ticket_types.*.quantity_available' => ['required', 'integer', 'min:1'],
            'ticket_types.*.max_per_order' => ['nullable', 'integer', 'min:1', 'max:20'],
            'ticket_types.*.sale_start_date' => ['nullable', 'date_format:Y-m-d'],
            'ticket_types.*.sale_end_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'ticket_types.max' => 'Vingt types de billets au maximum.',
            'ticket_types.*.name.required' => 'Indiquez le nom du billet.',
            'ticket_types.*.price.required' => 'Indiquez le prix (0 pour un billet gratuit).',
            'ticket_types.*.price.integer' => 'Le prix doit être un nombre entier de FCFA.',
            'ticket_types.*.price.min' => 'Le prix ne peut pas être négatif.',
            'ticket_types.*.quantity_available.required' => 'Indiquez la quantité.',
            'ticket_types.*.quantity_available.integer' => 'La quantité doit être un nombre entier.',
            'ticket_types.*.quantity_available.min' => 'La quantité doit être au moins 1.',
            'ticket_types.*.max_per_order.integer' => 'Le maximum par commande doit être un nombre entier.',
            'ticket_types.*.max_per_order.min' => 'Le maximum par commande doit être au moins 1.',
            'ticket_types.*.max_per_order.max' => 'Le maximum par commande ne peut pas dépasser 20.',
            'ticket_types.*.sale_start_date.date_format' => 'La date de début de vente n\'est pas valide.',
            'ticket_types.*.sale_end_date.date_format' => 'La date de fin de vente n\'est pas valide.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tickets = $this->input('ticket_types');
            if (! is_array($tickets)) {
                return;
            }
            $eventStart = $this->submittedEventStart($validator);

            foreach ($tickets as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $errors = $validator->errors();
                $quantity = $row['quantity_available'] ?? null;
                $max = $row['max_per_order'] ?? null;

                if (is_numeric($quantity) && is_numeric($max) && ! $errors->has("ticket_types.$i.quantity_available")
                    && ! $errors->has("ticket_types.$i.max_per_order") && (int) $max > (int) $quantity) {
                    $validator->errors()->add("ticket_types.$i.max_per_order", 'Le maximum par commande ne peut pas dépasser la quantité disponible.');
                }

                if ($eventStart && ! $errors->has("ticket_types.$i.sale_start_date") && ! $errors->has("ticket_types.$i.sale_end_date")) {
                    $violation = SalePeriod::violation($eventStart, $row['sale_start_date'] ?? null, $row['sale_end_date'] ?? null);
                    if ($violation) {
                        $validator->errors()->add("ticket_types.$i.{$violation[0]}", $violation[1]);
                    }
                }
            }
        });
    }

    /** Billets validés, dans l'ordre de saisie (liste explicite de champs). */
    public function ticketsData(): array
    {
        $out = [];
        foreach ((array) ($this->validated()['ticket_types'] ?? []) as $row) {
            $out[] = array_intersect_key($row, array_flip([
                'name', 'description', 'price', 'quantity_available', 'max_per_order', 'sale_start_date', 'sale_end_date',
            ]));
        }

        return $out;
    }

    /** Le promoteur a-t-il cliqué sur « Publier » ? */
    public function wantsPublication(): bool
    {
        return $this->input('action') === 'publish';
    }
}
