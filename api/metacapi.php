<?php
require_once __DIR__ . '/config.php';

function metacapi_purchase(array $tx): void {
    if (META_PIXEL_ID === 'YOUR_META_PIXEL_ID_HERE') return;

    $sha = function(?string $v): ?string {
        if ($v === null || $v === '') return null;
        return hash('sha256', strtolower(trim($v)));
    };

    $nameParts = explode(' ', trim($tx['payer']['name'] ?? ''), 2);
    $phone = preg_replace('/\D/', '', $tx['payer']['phone'] ?? '');

    $userData = array_filter([
        'em'                => array_filter([$sha($tx['payer']['email'] ?? null)]),
        'ph'                => array_filter([$sha($phone ?: null)]),
        'fn'                => array_filter([$sha($nameParts[0] ?? null)]),
        'ln'                => array_filter([$sha($nameParts[1] ?? null)]),
        'fbc'               => $tx['fbc']       ?? null,
        'fbp'               => $tx['fbp']       ?? null,
        'client_ip_address' => $tx['ip']        ?? null,
        'client_user_agent' => $tx['userAgent'] ?? null,
    ]);

    $event = [
        'event_name'       => 'Purchase',
        'event_time'       => time(),
        'action_source'    => 'website',
        'event_source_url' => APP_URL . '/pagamento.html',
        'event_id'         => $tx['transactionID'],
        'user_data'        => $userData,
        'custom_data'      => [
            'value'    => (float) $tx['amount'],
            'currency' => 'EUR',
            'order_id' => $tx['transactionID'],
        ],
    ];

    $url = 'https://graph.facebook.com/v18.0/' . META_PIXEL_ID
         . '/events?access_token=' . META_ACCESS_TOKEN;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['data' => [$event]]),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    error_log('[META CAPI] Purchase → ' . $res);
}
