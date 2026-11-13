<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseInfo;
use App\Support\Media;

/** Page Sauvegardes : information seule, aucune action de sauvegarde ni de restauration (middleware admin du groupe de routes). */
class BackupController extends Controller
{
    public function index(DatabaseInfo $database)
    {
        $disk = Media::diskName();

        return view('admin.backups.index', [
            'database' => $database->summary(),
            'mediaDisk' => $disk,
            'mediaOffServer' => (string) config('filesystems.disks.'.$disk.'.driver') === 'r2',
            'exports' => [
                ['route' => 'admin.users.export', 'label' => 'Utilisateurs', 'icon' => 'fa-users'],
                ['route' => 'admin.events.export', 'label' => 'Événements', 'icon' => 'fa-calendar-alt'],
                ['route' => 'admin.orders.export', 'label' => 'Commandes', 'icon' => 'fa-shopping-cart'],
                ['route' => 'admin.tickets.export', 'label' => 'Billets', 'icon' => 'fa-ticket-alt'],
            ],
        ]);
    }
}
