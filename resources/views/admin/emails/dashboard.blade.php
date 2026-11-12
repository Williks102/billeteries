@extends('layouts.admin')

@section('title', 'Emails')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Emails</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Emails" subtitle="Envoi de test et modèles de messages">
        <a href="{{ route('admin.emails.templates') }}" class="btn btn-outline-primary">
            <i class="fas fa-envelope-open-text" aria-hidden="true"></i> Modèles
        </a>
    </x-admin.page-header>

    <div class="alert alert-info" role="note">
        Le suivi des emails (envoyés, rebonds, ouvertures, clics) n'est pas encore en place : aucune statistique n'est affichée.
        Les confirmations de commande peuvent être renvoyées depuis la fiche de chaque commande.
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Envoyer un email de test</h2></div>
                <div class="card-body">
                    <form id="test-email-form" action="{{ route('admin.emails.test') }}" method="POST" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse destinataire</label>
                            <input type="email" id="email" name="email" class="form-control" required autocomplete="email"
                                   aria-describedby="test-email-result">
                        </div>
                        <div class="action-bar mt-0">
                            <button type="submit" class="btn btn-primary">Envoyer le test</button>
                        </div>
                        <div id="test-email-result" class="mt-3" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('test-email-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = e.target, out = document.getElementById('test-email-result');
    out.className = 'mt-3 text-muted';
    out.textContent = 'Envoi en cours…';
    fetch(form.action, {
        method: 'POST',
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value},
        body: new FormData(form)
    }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
      .then(function (res) {
          out.className = 'mt-3 ' + (res.ok ? 'text-success' : 'text-danger');
          out.textContent = res.d.message || (res.d.errors ? Object.values(res.d.errors)[0][0] : 'Erreur');
      }).catch(function () { out.className = 'mt-3 text-danger'; out.textContent = 'Erreur réseau.'; });
});
</script>
@endpush
