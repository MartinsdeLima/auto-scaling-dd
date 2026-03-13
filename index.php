<?php

header('Content-Type: text/plain; charset=utf-8');

$start = microtime(true);

$agent = getenv("DD_AGENT_HOST") ?: "localhost";
$port  = 8125;

function dogstatsd($metric, $value, $type="c", $tags=[]) {
    global $agent, $port;

    $tagstr = "";
    if (!empty($tags)) {
        $tagstr = "|#" . implode(",", $tags);
    }

    $msg = "$metric:$value|$type$tagstr";

    $fp = fsockopen("udp://$agent", $port);
    if ($fp) {
        fwrite($fp, $msg);
        fclose($fp);
    }
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);

dogstatsd("php.request.count", 1, "c", ["endpoint:$uri"]);

if ($uri === '/ping') {

    http_response_code(200);
    echo "pong";

} elseif ($uri === '/') {

    http_response_code(200);
    echo "Hello from PHP!";

} else {

    http_response_code(404);
    echo "Not Found";

}

$duration = (microtime(true) - $start) * 1000;

dogstatsd(
    "php.request.duration",
    $duration,
    "g",
    ["endpoint:$uri"]
);