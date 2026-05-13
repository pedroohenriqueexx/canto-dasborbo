<?php
require_once __DIR__ . '/utmify.php';
require_once __DIR__ . '/metacapi.php';

// WayMB exige HTTP 200 imediato
http_response_code(200);
echo 'OK';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) exit;

$transactionID = $body['transactionID'] ?? '';
$status        = $body['status']        ?? '';
$amount        = (float) ($body['amount']   ?? 0);
$currency      = $body['currency']      ?? 'EUR';
$payer         = $body['payer']         ?? [];

error_log("[WEBHOOK] {$transactionID} → {$status} ({$amount} {$currency})");

// ── Carregar dados guardados na criação ───────────────────────────────────────
$txFile = __DIR__ . '/transactions/' . $transactionID . '.json';
$txData = file_exists($txFile) ? json_decode(file_get_contents($txFile), true) : [];

// Complementar com dados do webhook (WayMB pode enviar dados mais completos)
$txData['transactionID'] = $transactionID;
$txData['amount']        = $amount;
if (!empty($payer)) {
    $txData['payer'] = array_merge($txData['payer'] ?? [], $payer);
}

switch ($status) {
    case 'COMPLETED':
        error_log("[WEBHOOK] Pago: {$transactionID} — {$amount} {$currency} — " . ($payer['name'] ?? ''));
        utmify_send($txData, 'paid');
        metacapi_purchase($txData);
        // Apagar ficheiro após uso
        if (file_exists($txFile)) unlink($txFile);
        break;

    case 'DECLINED':
        error_log("[WEBHOOK] Recusado: {$transactionID}");
        utmify_send($txData, 'refused');
        if (file_exists($txFile)) unlink($txFile);
        break;

    case 'PENDING':
        error_log("[WEBHOOK] Pendente: {$transactionID}");
        break;
}
