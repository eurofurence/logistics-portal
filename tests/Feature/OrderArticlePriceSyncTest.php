<?php

use App\Models\Order;
use App\Models\OrderArticle;
use App\Models\OrderEvent;
use App\Models\User;

test('synchronizes catalog prices to open orders', function (array $changes, bool $autoCalculate, float $expectedNet, float $expectedGross) {
    $this->actingAs(User::factory()->create());
    $article = OrderArticle::factory()->create(['auto_calculate' => $autoCalculate]);
    $order = Order::factory()->create([
        'order_article_id' => $article->id,
        'price_net' => 10,
        'price_gross' => 11.90,
        'tax_rate' => 19,
    ]);

    $article->update($changes);

    expect($article->fresh()->price_gross)->toBe($expectedGross);
    expect($order->fresh())
        ->price_net->toBe($expectedNet)
        ->price_gross->toBe($expectedGross)
        ->tax_rate->toBe($article->tax_rate);
})->with([
    'net price' => [['price_net' => 20], true, 20.0, 23.8],
    'zero net price' => [['price_net' => 0], true, 0.0, 0.0],
    'tax rate' => [['tax_rate' => 7], true, 10.0, 10.7],
    'explicit price pair' => [['price_net' => 20, 'price_gross' => 25], true, 20.0, 25.0],
    'gross price only' => [['price_gross' => 12], true, 10.0, 12.0],
    'manual calculation' => [['price_net' => 20], false, 20.0, 11.9],
]);

test('preserves prices of orders outside the catalog synchronization scope', function (string $status, bool $locked) {
    $this->actingAs(User::factory()->create());
    $article = OrderArticle::factory()->create();
    $event = OrderEvent::factory()->create(['locked' => $locked]);
    $order = Order::factory()->create([
        'order_article_id' => $article->id,
        'order_event_id' => $event->id,
        'status' => $status,
        'price_net' => 10,
        'price_gross' => 11.90,
    ]);

    $article->update(['price_net' => 20]);

    expect($order->fresh())->price_net->toBe(10.0)->price_gross->toBe(11.9);
})->with([
    'locked event' => ['open', true],
    'ordered' => ['ordered', false],
    'awaiting approval' => ['awaiting_approval', false],
]);
