<?php

header('Content-Type: text/plain; charset=utf-8');

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);

if ($uri === '/ping') {
    echo "pong";
    http_response_code(200);
    exit;
}

if ($uri === '/') {
    echo "Hello from PHP!";
    http_response_code(200);
    exit;
}

echo "Not Found";
http_response_code(404);