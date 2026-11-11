{{--
    Un bloc « type de billet » (champs + estimation de recette).
    Paramètres :
      $prefix   '' (édition : noms plats) ou 'ticket_types[3]' (création : tableau)
      $errKey   '' ou 'ticket_types.3.' (préfixe des clés d'erreur de validation)
      $uid      identifiant unique du bloc (sert aux id / aria-describedby)
      $values   name, description, price, quantity_available, max_per_order, sale_start_date, sale_end_date
      $event    événement (date maximale de fin de vente)
      $isCreate bool : contraintes de dates côté navigateur (min = aujourd'hui)
      $minQuantity quantité minimale (billets déjà vendus en édition)
--}}
@php
    $field = fn (string $f) => $prefix === '' ? $f : $prefix.'['.$f.']';
    $eventDate = $event->event_date ? $event->event_date->format('Y-m-d') : '';
    $minQuantity = $minQuantity ?? 1;
@endphp
<fieldset class="tt-block" data-ticket-block>
    <legend class="tt-block-title">
        <i class="fas fa-ticket-alt me-2 tt-accent" aria-hidden="true"></i>
        <span>{{ $isCreate ? 'Type de billet' : 'Billet' }} <span data-block-number></span></span>
    </legend>
    @if ($isCreate)
        <button type="button" class="btn btn-outline-danger tt-remove" data-remove-block aria-label="Supprimer ce type de billet">
            <i class="fas fa-trash-alt" aria-hidden="true"></i>
        </button>
    @endif

    <div class="row g-3">
        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-name">Nom du billet <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="{{ $uid }}-name" name="{{ $field('name') }}" required maxlength="255"
                   value="{{ $values['name'] ?? '' }}" autocomplete="off" enterkeyhint="next"
                   class="form-control @error($errKey.'name') is-invalid @enderror"
                   placeholder="Ex : Standard, VIP, Early Bird"
                   @error($errKey.'name') aria-invalid="true" aria-describedby="{{ $uid }}-name-error" @enderror>
            @error($errKey.'name')
                <div id="{{ $uid }}-name-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-price">Prix (FCFA) <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="number" id="{{ $uid }}-price" name="{{ $field('price') }}" required min="0" step="1"
                   inputmode="numeric" pattern="[0-9]*" autocomplete="off" enterkeyhint="next"
                   value="{{ $values['price'] ?? '' }}" data-field="price"
                   class="form-control @error($errKey.'price') is-invalid @enderror"
                   placeholder="Ex : 5000 (0 = gratuit)"
                   @error($errKey.'price') aria-invalid="true" aria-describedby="{{ $uid }}-price-error" @enderror>
            @error($errKey.'price')
                <div id="{{ $uid }}-price-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="{{ $uid }}-description">Description <span class="text-muted small">(facultatif)</span></label>
            <textarea id="{{ $uid }}-description" name="{{ $field('description') }}" rows="2" maxlength="500" autocomplete="off"
                      class="form-control @error($errKey.'description') is-invalid @enderror"
                      placeholder="Ce que comprend ce billet"
                      @error($errKey.'description') aria-invalid="true" aria-describedby="{{ $uid }}-description-error" @enderror>{{ $values['description'] ?? '' }}</textarea>
            @error($errKey.'description')
                <div id="{{ $uid }}-description-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-quantity">Quantité disponible <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="number" id="{{ $uid }}-quantity" name="{{ $field('quantity_available') }}" required min="{{ $minQuantity }}" step="1"
                   inputmode="numeric" pattern="[0-9]*" autocomplete="off" enterkeyhint="next"
                   value="{{ $values['quantity_available'] ?? '' }}" data-field="quantity"
                   class="form-control @error($errKey.'quantity_available') is-invalid @enderror"
                   aria-describedby="{{ $uid }}-quantity-hint @error($errKey.'quantity_available') {{ $uid }}-quantity-error @enderror"
                   @error($errKey.'quantity_available') aria-invalid="true" @enderror>
            @if ($minQuantity > 1)
                <div id="{{ $uid }}-quantity-hint" class="form-text">Minimum {{ $minQuantity }} (billets déjà vendus).</div>
            @else
                <div id="{{ $uid }}-quantity-hint" class="form-text">Nombre total de billets en vente.</div>
            @endif
            @error($errKey.'quantity_available')
                <div id="{{ $uid }}-quantity-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-max">Maximum par commande <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="number" id="{{ $uid }}-max" name="{{ $field('max_per_order') }}" required min="1" max="20" step="1"
                   inputmode="numeric" pattern="[0-9]*" autocomplete="off" enterkeyhint="next"
                   value="{{ $values['max_per_order'] ?? '' }}" data-field="max_per_order"
                   class="form-control @error($errKey.'max_per_order') is-invalid @enderror"
                   aria-describedby="{{ $uid }}-max-hint @error($errKey.'max_per_order') {{ $uid }}-max-error @enderror"
                   @error($errKey.'max_per_order') aria-invalid="true" @enderror>
            <div id="{{ $uid }}-max-hint" class="form-text">Entre 1 et 20 billets.</div>
            @error($errKey.'max_per_order')
                <div id="{{ $uid }}-max-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-start">Début de la vente <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="date" id="{{ $uid }}-start" name="{{ $field('sale_start_date') }}" required autocomplete="off"
                   value="{{ $values['sale_start_date'] ?? '' }}" data-field="start"
                   @if ($isCreate) min="{{ now()->format('Y-m-d') }}" @endif
                   class="form-control @error($errKey.'sale_start_date') is-invalid @enderror"
                   @error($errKey.'sale_start_date') aria-invalid="true" aria-describedby="{{ $uid }}-start-error" @enderror>
            @error($errKey.'sale_start_date')
                <div id="{{ $uid }}-start-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-sm-6">
            <label class="form-label" for="{{ $uid }}-end">Fin de la vente <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="date" id="{{ $uid }}-end" name="{{ $field('sale_end_date') }}" required autocomplete="off"
                   value="{{ $values['sale_end_date'] ?? '' }}" data-field="end"
                   @if ($isCreate && $eventDate !== '') max="{{ $eventDate }}" @endif
                   class="form-control @error($errKey.'sale_end_date') is-invalid @enderror"
                   @error($errKey.'sale_end_date') aria-invalid="true" aria-describedby="{{ $uid }}-end-error" @enderror>
            @error($errKey.'sale_end_date')
                <div id="{{ $uid }}-end-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="tt-revenue" aria-live="polite">
        <span class="text-muted">Recette brute potentielle</span>
        <strong data-revenue>0 FCFA</strong>
    </div>
</fieldset>
