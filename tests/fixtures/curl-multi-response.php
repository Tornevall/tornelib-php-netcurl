<?php

// Deterministic localhost response for cURL multi-request regression tests.
$case = isset($_GET['case']) ? (string)$_GET['case'] : 'unknown';
$requestId = isset($_SERVER['HTTP_X_TEST_REQUEST']) ? (string)$_SERVER['HTTP_X_TEST_REQUEST'] : 'missing';

$statusCodes = [
    'first' => 200,
    'second' => 201,
    'duplicate' => 202,
];

http_response_code(isset($statusCodes[$case]) ? $statusCodes[$case] : 400);
header('Content-Type: ' . ($case === 'second' ? 'application/json; charset=utf-8' : 'application/json'));
header('X-Request-ID: ' . $requestId);
header('X-Response-Case: ' . $case);

echo json_encode([
    'case' => $case,
    'request_id' => $requestId,
    'method' => $_SERVER['REQUEST_METHOD'],
]);
