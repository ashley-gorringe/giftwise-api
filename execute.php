<?php
//Sets up the full PHP environment, loads in dependancies and functions.
define('BASE_PATH',dirname($_SERVER['DOCUMENT_ROOT']).'/');
session_start();
date_default_timezone_set('Europe/London');
header('Content-Type: application/json');
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Access-Control-Allow-Origin: *");

require BASE_PATH.'vendor/autoload.php';

//Loads in the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

//Sets up the database
use Dandylion\AdvancedMedoo;
$database = new AdvancedMedoo([
    'database_type' => $_ENV['DB_TYPE'],
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
]);

//Sets up the HTTP router
use Phroute\Phroute\RouteCollector;
$router = new RouteCollector();

//Sets up the routes
$router->any('/', function(){
    http_response_code(400);
});

$router->post('/users', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/users-post.php';
});
$router->get('/users', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/users-get.php';
});
$router->post('/login_tokens', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/login_tokens-post.php';
});
$router->get('/login_tokens/{token}', function($token){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/login_tokens-get.php';
});

$router->post('/items', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/items-post.php';
});

//Dispatches the routes
$dispatcher = new Phroute\Phroute\Dispatcher($router->getData());
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
echo $response;

?>