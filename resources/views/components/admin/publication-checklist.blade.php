@props(['checklist', 'event'])
{{-- Checklist de publication (admin) : mêmes règles que le promoteur (Event::publicationChecklist). Brouillon seulement. --}}
@if ($event->status === 'draft')
<section class="card mb-4" aria-labelledby="publication-checklist-title" data-publication-checklist>
    <div class="card-body">
        <h2 id="publication-checklist-title" class="h5 mb-3">Avant de publier</h2>

        @if ($checklist['blocking'] === [])
            <p class="mb-2 text-success"><i class="fas fa-check-circle me-1" aria-hidden="true"></i>Tout est prêt : vous pouvez publier l'événement.</p>
        @else
            <p class="mb-2 fw-semibold">Il manque :</p>
            <ul class="list-unstyled mb-2" data-checklist-blocking>
                @foreach ($checklist['blocking'] as $code => $message)
                    <li class="d-flex gap-2 mb-1" data-code="{{ $code }}">
                        <i class="fas fa-times-circle mt-1" style="color: var(--state-failed);" aria-hidden="true"></i>
                        <span><span class="visually-hidden">Bloquant : </span>{{ $message }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="small text-muted mb-2">Les billets et l'image se complètent depuis la fiche du promoteur ou via « Modifier » (image, date).</p>
        @endif

        @if ($checklist['warnings'] !== [])
            <ul class="list-unstyled mb-2 small text-muted" data-checklist-warnings>
                @foreach ($checklist['warnings'] as $code => $message)
                    <li class="d-flex gap-2 mb-1" data-code="{{ $code }}">
                        <i class="fas fa-exclamation-triangle mt-1" style="color: var(--state-pending);" aria-hidden="true"></i>
                        <span><span class="visually-hidden">Conseil : </span>{{ $message }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('admin.events.quick-publish', $event) }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-success" data-publish-button @disabled(! $checklist['can_publish'])>
                <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Publier
            </button>
        </form>
    </div>
</section>
@endif
