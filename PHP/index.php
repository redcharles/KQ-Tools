<?php

require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/controller/api.php';

$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/' :
        require __DIR__ . '/routes/index.php';
    break;
    
    case '' :
        require __DIR__ . '/routes/index.php';
    break;

    
    case '/api/post' :
        require __DIR__ . '/routes/post.php';
    break;

    default: 
        http_response_code(404);
        require __DIR__ . '/routes/404.php';
    break;
}
