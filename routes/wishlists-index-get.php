<?php

$response = (object)[];

checkAuth();

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

$response->wishlists = getWishlists($user_id);

return json_encode($response);