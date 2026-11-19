<?php

// Local HTTP echo for query-string and request-body regression tests.
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$query = parse_url($uri, PHP_URL_QUERY);
if (!is_string($query)) {
    $query = '';
}

header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $uri,
    'raw_query' => $query,
    'params' => $_GET,
    'body' => file_get_contents('php://input'),
    'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '',
]);
