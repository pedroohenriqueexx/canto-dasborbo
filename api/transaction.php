<?php
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$txFile = __DIR__ . '/transactions/' . $id . '.json';

if (!file_exists($txFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Transação não encontrada']);
    exit;
}

$txData = json_decode(file_get_contents($txFile), true);

if (!$txData) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao ler transação']);
    exit;
}

$statusMap = [
    'paid'    => 'paid',
    'declined' => 'failed',
    'pending' => 'pending',
];

$result = [
    'status' => $statusMap[$txData['status'] ?? 'pending'] ?? 'pending',
    'amount' => $txData['amount'] ?? 0,
];

if (!empty($txData['referenceData'])) {
    $result['referenceData'] = $txData['referenceData'];
}

echo json_encode(['success' => true, 'data' => $result]);
