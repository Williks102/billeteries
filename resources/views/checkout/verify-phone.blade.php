{{-- resources/views/checkout/verify-phone.blade.php --}}
@extends('layouts.app')

@section('title', 'Vérification téléphone - ClicBillet')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Vérification téléphone</h1>
            <p class="text-gray-600">
                Un code de vérification a été envoyé au numéro
                <strong>{{ $phone }}</strong>
            </p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Code de développement --}}
        @if(isset($devCode))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <strong>Mode développement :</strong> Votre code est <strong>{{ $devCode }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.process') }}">
            @csrf
            
            {{-- Champs cachés pour maintenir les données --}}
            <input type="hidden" name="phone" value="{{ $phone }}">
            <input type="hidden" name="name" value="{{ $userName }}">
            
            <div class="mb-4">
                <label for="otp_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Code de vérification
                </label>
                <input 
                    type="text" 
                    id="otp_code" 
                    name="otp_code" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-center text-lg tracking-widest" 
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    autocomplete="one-time-code"
                    required
                    autofocus
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
            >
                Vérifier et finaliser ma commande
            </button>
        </form>

        {{-- Bouton pour renvoyer le code --}}
        <div class="text-center mt-4">
            <button 
                id="resend-code" 
                class="text-blue-600 hover:text-blue-800 text-sm"
                data-action="resend-code"
            >
                Renvoyer le code
            </button>
        </div>

        {{-- Informations de la commande --}}
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Récapitulatif</h3>
            <div class="text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Total à payer :</span>
                    <span class="font-medium">{{ number_format($cartTotal / 100, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="checkout-verify-phone-config"
     data-resend-url="{{ route('checkout.resend-code') }}"
     data-csrf="{{ csrf_token() }}"
     data-phone="{{ $phone }}"
     hidden></div>
<script src="{{ asset('js/checkout-verify-phone.js') }}" defer></script>
@endsection