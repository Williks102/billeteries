{{-- Styles mobile-first communs aux pages admin (champs 16px pour éviter le zoom iOS, cibles tactiles 44px). --}}
@once
    @push('styles')
        <style>
            .admin-page .form-control,
            .admin-page .form-select { font-size: 16px; min-height: 44px; }
            .admin-page .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; }
            .admin-page .btn:focus-visible,
            .admin-page .form-control:focus-visible,
            .admin-page .form-select:focus-visible,
            .admin-page a:focus-visible { outline: 3px solid #0d6efd; outline-offset: 2px; }
            .admin-page .stat-value { font-size: 1.25rem; word-break: break-word; }
            .admin-page .table td, .admin-page .table th { vertical-align: middle; }
        </style>
    @endpush
@endonce
