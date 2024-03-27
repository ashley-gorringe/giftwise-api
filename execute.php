<?php
//Sets up the full PHP environment, loads in dependancies and functions.
define('BASE_PATH',dirname($_SERVER['DOCUMENT_ROOT']).'/');
session_start();
date_default_timezone_set('Europe/London');
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return only the headers and not the content
    // Only allow CORS if we're doing a GET - this is a preflight request
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization, Token');
    exit;
}


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

use Aws\S3\S3Client;
$s3_client_credentials = new Aws\Credentials\Credentials($_ENV['R2_ACCESS_KEY_ID'], $_ENV['R2_ACCESS_KEY_SECRET']);
$s3_client = new Aws\S3\S3Client([
    'region' => 'auto',
    'endpoint' => "https://".$_ENV['R2_ACCOUNT_ID'].".r2.cloudflarestorage.com",
    'version' => 'latest',
    'credentials' => $s3_client_credentials
]);

//Sets up the image manager
use Intervention\Image\ImageManager;
$imageManager = new ImageManager(['driver' => 'gd']);

//Sets up the HTTP router
use Phroute\Phroute\RouteCollector;
$router = new RouteCollector();

require_once dirname($_SERVER['DOCUMENT_ROOT']).'/functions.php';

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
$router->delete('/items/{uid}', function($uid){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/items-delete.php';
});

$router->get('/wishlists/{uid}', function($uid){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlists-get.php';
});
$router->get('/wishlists', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlists-index-get.php';
});
$router->post('/wishlists', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlists-post.php';
});
$router->delete('/wishlists/{uid}', function($uid){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlists-delete.php';
});

$router->get('/wishlist_share/{uid}', function($uid){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlist-share-get.php';
});
$router->post('/wishlist_share', function(){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlist_share-post.php';
});
$router->delete('/wishlist_share/{uid}', function($uid){
    return require_once dirname($_SERVER['DOCUMENT_ROOT']).'/routes/wishlist-share-delete.php';
});

//Dispatches the routes
$dispatcher = new Phroute\Phroute\Dispatcher($router->getData());
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
echo $response;

?>