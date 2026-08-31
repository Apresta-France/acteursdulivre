<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Models\Conversation;
use Adl\Models\Order;
use Adl\Models\Service;

$service = Service::findBySlug('correction-complete-roman-ou-essai');
if (!$service) {
    fwrite(STDERR, "service missing\n");
    exit(1);
}

echo "service id=" . $service['id'] . " status=" . $service['status'] . " seller=" . $service['user_id'] . PHP_EOL;
echo "packages=" . count($service['packages'] ?? []) . " options=" . count($service['options'] ?? []) . PHP_EOL;

try {
    $order = Order::create([
        'buyer_id' => 8,
        'seller_id' => (int) $service['user_id'],
        'service_id' => (int) $service['id'],
        'amount' => (int) ($service['price_from'] ?? 0),
        'brief' => 'Test diagnostic achat fiche',
        'package_name' => $service['packages'][0]['name'] ?? null,
        'options' => [],
    ]);
    echo "order ok id=" . $order['id'] . " num=" . $order['num'] . PHP_EOL;
    Conversation::open(8, (int) $service['user_id'], [
        'subject' => (string) $service['title'],
        'order_id' => (int) $order['id'],
        'service_id' => (int) $service['id'],
    ]);
    echo "conversation ok\n";
} catch (Throwable $e) {
    echo "FAIL " . $e::class . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
