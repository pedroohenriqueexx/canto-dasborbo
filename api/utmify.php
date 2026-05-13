<?php
require_once __DIR__ . '/config.php';

function utmify_send(array $tx, string $status): void {
    if (UTMIFY_TOKEN === 'YOUR_UTMIFY_TOKEN_HERE') return;

    $methodMap = ['mbway' => 'pix', 'multibanco' => 'boleto'];
    $amountCents = (int) round($tx['amount'] * 100);
    $now = gmdate('Y-m-d H:i:s');

    $payload = [
        'orderId'       => $tx['transactionID'],
        'platform'      => 'WayMB',
        'paymentMethod' => $methodMap[$tx['method']] ?? 'pix',
        'status'        => $status,
        'createdAt'     => $tx['createdAt'] ?? $now,
        'approvedDate'  => $status === 'paid'    ? $now : null,
        'refundedAt'    => $status === 'refunded' ? $now : null,
        'customer' => [
            'name'     => $tx['payer']['name']     ?? '',
            'email'    => $tx['payer']['email']    ?? '',
            'phone'    => $tx['payer']['phone']    ?? null,
            'document' => $tx['payer']['document'] ?? null,
            'country'  => 'PT',
            'ip'       => $tx['ip']                ?? null,
        ],
        'products' => [[
            'id'           => 'cantinho-borboletas-donativo',
            'name'         => 'Donativo Cantinho das Borboletas',
            'planId'       => null,
            'planName'     => null,
            'quantity'     => 1,
            'priceInCents' => $amountCents,
        ]],
        'trackingParameters' => [
            'src'          => $tx['utm']['src']          ?? null,
            'sck'          => $tx['utm']['sck']          ?? null,
            'utm_source'   => $tx['utm']['utm_source']   ?? null,
            'utm_campaign' => $tx['utm']['utm_campaign'] ?? null,
            'utm_medium'   => $tx['utm']['utm_medium']   ?? null,
            'utm_content'  => $tx['utm']['utm_content']  ?? null,
            'utm_term'     => $tx['utm']['utm_term']     ?? null,
        ],
        'commission' => [
            'totalPriceInCents'     => $amountCents,
            'gatewayFeeInCents'     => 0,
            'userCommissionInCents' => $amountCents,
            'currency'              => 'EUR',
        ],
        'isTest' => false,
    ];

    $ch = curl_init('https://api.utmify.com.br/api-credentials/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-token: ' . UTMIFY_TOKEN,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    error_log('[UTMIFY] ' . $status . ' → ' . $res);
}
