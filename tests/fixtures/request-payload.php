<?php

// Local-only fixture for verifying that consecutive requests have independent bodies.
header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'body' => file_get_contents('php://input'),
    'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '',
]);
