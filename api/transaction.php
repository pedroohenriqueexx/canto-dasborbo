<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$ch = curl_init(WAYMB_API . '/transactions/info');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'client_id'     => WAYMB_CLIENT_ID,
        'client_secret' => WAYMB_CLIENT_SECRET,
        'transactionID' => $id,
    ]),
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode ?: 500);
    echo json_encode(['success' => false, 'message' => $data['message'] ?? 'Erro ao consultar transação']);
    exit;
}

$statusMap = ['COMPLETED' => 'paid', 'DECLINED' => 'failed', 'PENDING' => 'pending'];

$result = [
    'status' => $statusMap[$data['status']] ?? 'pending',
    'amount' => $data['amount'],
];

if (!empty($data['referenceData'])) {
    $result['referenceData'] = [
        'entity'    => $data['referenceData']['entity']    ?? null,
        'reference' => $data['referenceData']['reference'] ?? null,
        'expiresAt' => $data['referenceData']['expiresAt'] ?? $data['referenceData']['expiry'] ?? null,
    ];
}

echo json_encode(['success' => true, 'data' => $result]);
