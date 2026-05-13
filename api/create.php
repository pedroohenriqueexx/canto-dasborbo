<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utmify.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$amountEur = $body['amount'] / 100;

// ── Extrair UTMs do checkoutUrl ───────────────────────────────────────────────
$utms = ['src' => null, 'sck' => null, 'utm_source' => null,
         'utm_campaign' => null, 'utm_medium' => null,
         'utm_content' => null, 'utm_term' => null];

if (!empty($body['checkoutUrl'])) {
    $qs = parse_url($body['checkoutUrl'], PHP_URL_QUERY);
    if ($qs) {
        parse_str($qs, $params);
        foreach (array_keys($utms) as $key) {
            if (!empty($params[$key])) $utms[$key] = $params[$key];
        }
    }
}

// ── IP real do visitante ──────────────────────────────────────────────────────
$ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['REMOTE_ADDR']
    ?? null;
if ($ip) $ip = trim(explode(',', $ip)[0]);

// ── Criar transação WayMB ─────────────────────────────────────────────────────
$payload = [
    'client_id'          => WAYMB_CLIENT_ID,
    'client_secret'      => WAYMB_CLIENT_SECRET,
    'account_email'      => WAYMB_ACCOUNT_EMAIL,
    'amount'             => $amountEur,
    'method'             => $body['method'],
    'payer'              => [
        'email'    => !empty($body['email']) ? $body['email'] : 'doador@cantinhodasborboletas.pt',
        'name'     => $body['name'],
        'document' => $body['document'],
        'phone'    => $body['phoneNumber'],
    ],
    'paymentDescription' => 'Donativo Cantinho das Borboletas',
    'currency'           => 'EUR',
    'callbackUrl'        => APP_URL . '/api/webhook',
];

$ch = curl_init(WAYMB_API . '/transactions/create');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode ?: 500);
    echo json_encode(['success' => false, 'message' => $data['message'] ?? 'Erro ao criar transação']);
    exit;
}

// ── Guardar dados da transação para o webhook usar ────────────────────────────
$txData = [
    'transactionID' => $data['transactionID'],
    'method'        => $body['method'],
    'amount'        => $amountEur,
    'status'        => 'pending',
    'createdAt'     => gmdate('Y-m-d H:i:s'),
    'payer'         => [
        'name'     => $body['name'],
        'email'    => !empty($body['email']) ? $body['email'] : null,
        'phone'    => $body['phoneNumber'],
        'document' => $body['document'],
    ],
    'utm'       => $utms,
    'ip'        => $ip,
    'userAgent' => $body['userAgent']  ?? null,
    'fbc'       => $body['_fbc']       ?? null,
    'fbp'       => $body['_fbp']       ?? null,
];

if (!empty($data['referenceData'])) {
    $txData['referenceData'] = [
        'entity'    => $data['referenceData']['entity']    ?? null,
        'reference' => $data['referenceData']['reference'] ?? null,
        'expiresAt' => $data['referenceData']['expiresAt'] ?? $data['referenceData']['expiry'] ?? null,
    ];
}

$storageDir = __DIR__ . '/transactions';
if (!is_dir($storageDir)) mkdir($storageDir, 0750, true);
file_put_contents($storageDir . '/' . $data['transactionID'] . '.json', json_encode($txData));

// ── UTMify: waiting_payment ───────────────────────────────────────────────────
utmify_send($txData, 'waiting_payment');

// ── Resposta ao frontend ──────────────────────────────────────────────────────
$result = [
    'id'     => $data['transactionID'],
    'amount' => $amountEur,
    'method' => $body['method'],
];

if (!empty($data['referenceData'])) {
    $result['referenceData'] = [
        'entity'    => $data['referenceData']['entity']    ?? null,
        'reference' => $data['referenceData']['reference'] ?? null,
        'expiresAt' => $data['referenceData']['expiresAt'] ?? $data['referenceData']['expiry'] ?? null,
    ];
}

echo json_encode(['success' => true, 'data' => $result]);
