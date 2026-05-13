<?php
// ── WayMB ─────────────────────────────────────────────────────────────────────
define('WAYMB_CLIENT_ID',     getenv('WAYMB_CLIENT_ID')     ?: '');
define('WAYMB_CLIENT_SECRET', getenv('WAYMB_CLIENT_SECRET') ?: '');
define('WAYMB_ACCOUNT_EMAIL', getenv('WAYMB_ACCOUNT_EMAIL') ?: '');
define('APP_URL',             getenv('APP_URL')             ?: '');
define('WAYMB_API',           'https://api.waymb.com');

// ── UTMify ────────────────────────────────────────────────────────────────────
define('UTMIFY_TOKEN',    getenv('UTMIFY_TOKEN')    ?: '');
define('UTMIFY_PIXEL_ID', getenv('UTMIFY_PIXEL_ID') ?: '');

// ── Meta CAPI ─────────────────────────────────────────────────────────────────
define('META_PIXEL_ID',     getenv('META_PIXEL_ID')     ?: '');
define('META_ACCESS_TOKEN', getenv('META_ACCESS_TOKEN') ?: '');
