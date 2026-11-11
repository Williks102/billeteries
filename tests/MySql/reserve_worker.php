<?php

/**
 * Processus de travail du test de concurrence MySQL (tests/MySql/EventPendingCapConcurrencyTest.php).
 * Usage : php reserve_worker.php <json {cart, user_id, barrier_dir, worker, total}>
 *
 * Reproduit le scénario N-A : la transaction établit un instantané REPEATABLE READ par une lecture
 * non verrouillante, tous les workers se synchronisent, PUIS réservent. Sans lecture verrouillante du
 * comptage, le 2e worker compterait avec un instantané périmé et dépasserait le plafond.
 * Sortie : une ligne JSON {"status": "accepted|refused|error", ...}. Aucun secret, aucune PII.
 */

use App\Exceptions\CheckoutRefusedException;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';

$args = json_decode($argv[1] ?? '', true);
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $service = app(CheckoutService::class);
    $groups = $service->buildEventGroups($args['cart']);

    DB::beginTransaction();
    DB::table('users')->where('id', $args['user_id'])->first(); // lecture NON verrouillante : fixe l'instantané

    // Barrière : tous les workers ont leur instantané avant que l'un d'eux ne réserve
    touch($args['barrier_dir'] . '/ready-' . $args['worker']);
    $deadline = microtime(true) + 60;
    while (count(glob($args['barrier_dir'] . '/ready-*')) < $args['total']) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('barrière expirée');
        }
        usleep(20000);
    }

    foreach ($groups as $eventId => $group) {
        $service->createOrder((int) $args['user_id'], (int) $eventId, $group, ['payment_method' => 'cash', 'billing_email' => 'w' . $args['worker'] . '@example.test']);
    }
    DB::commit();
    echo json_encode(['status' => 'accepted']), "\n";
} catch (CheckoutRefusedException $e) {
    DB::rollBack();
    echo json_encode(['status' => 'refused']), "\n";
} catch (Throwable $e) {
    try {
        DB::rollBack();
    } catch (Throwable) {
    }
    echo json_encode(['status' => 'error', 'class' => get_class($e), 'code' => (string) $e->getCode()]), "\n";
}
