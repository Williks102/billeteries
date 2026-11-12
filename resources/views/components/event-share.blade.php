@props(['event'])
{{-- Partage : WhatsApp, Facebook, X ; le bouton natif n'apparaît que si le navigateur le supporte (js/event-share.js). --}}
<section class="detail-section" aria-label="Partager cet événement">
    <h2 class="detail-section__title"><i class="fas fa-share-alt" aria-hidden="true"></i>Partager cet événement</h2>
    <div class="share-actions">
        @foreach($event->shareLinks(url()->current()) as $link)
        <a href="{{ $link['href'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary share-actions__btn">
            <i class="{{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}<span class="visually-hidden"> (s'ouvre dans un nouvel onglet)</span>
        </a>
        @endforeach
        <button type="button" class="btn btn-outline-primary share-actions__btn" data-native-share
                data-share-title="{{ $event->title }}" data-share-url="{{ url()->current() }}" hidden>
            <i class="fas fa-share-nodes" aria-hidden="true"></i>Partager
        </button>
    </div>
</section>
@once
@push('scripts')
<script src="{{ asset('js/event-share.js') }}" defer></script>
@endpush
@endonce
