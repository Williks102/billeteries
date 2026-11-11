@extends('layouts.app')

@section('title', 'Support')

@section('content')
<div class="container py-4" style="max-width:640px;">
    <h1 class="h3 mb-3">Support</h1>
    <p class="text-muted">Consultez d'abord la <a href="{{ route('pages.faq') }}">FAQ</a>. Sinon, décrivez votre demande ci-dessous.</p>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('pages.support.submit') }}" novalidate>
        @csrf
        @php
            $fieldStyle = 'font-size:16px;min-height:44px;';
        @endphp

        <div class="mb-3">
            <label for="support-name" class="form-label">Nom</label>
            <input type="text" id="support-name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"
                   class="form-control @error('name') is-invalid @enderror" style="{{ $fieldStyle }}"
                   @error('name') aria-invalid="true" aria-describedby="support-name-error" @enderror>
            @error('name')<div id="support-name-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="support-email" class="form-label">Adresse e-mail</label>
            <input type="email" id="support-email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   class="form-control @error('email') is-invalid @enderror" style="{{ $fieldStyle }}"
                   @error('email') aria-invalid="true" aria-describedby="support-email-error" @enderror>
            @error('email')<div id="support-email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="support-category" class="form-label">Catégorie</label>
            <select id="support-category" name="category" required class="form-select @error('category') is-invalid @enderror" style="{{ $fieldStyle }}"
                    @error('category') aria-invalid="true" aria-describedby="support-category-error" @enderror>
                @foreach(['Commande ou paiement', 'Billet', 'Compte', 'Espace promoteur', 'Autre'] as $option)
                    <option value="{{ $option }}" @selected(old('category') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('category')<div id="support-category-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="support-priority" class="form-label">Priorité</label>
            <select id="support-priority" name="priority" required class="form-select @error('priority') is-invalid @enderror" style="{{ $fieldStyle }}"
                    @error('priority') aria-invalid="true" aria-describedby="support-priority-error" @enderror>
                @foreach(['Normale', 'Urgente'] as $option)
                    <option value="{{ $option }}" @selected(old('priority') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('priority')<div id="support-priority-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="support-message" class="form-label">Message</label>
            <textarea id="support-message" name="message" rows="5" required minlength="10"
                      class="form-control @error('message') is-invalid @enderror" style="font-size:16px;"
                      @error('message') aria-invalid="true" aria-describedby="support-message-error" @enderror>{{ old('message') }}</textarea>
            @error('message')<div id="support-message-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="action-bar mt-0">
            <button type="submit" class="btn btn-primary">Envoyer ma demande</button>
        </div>
    </form>
</div>
@endsection
