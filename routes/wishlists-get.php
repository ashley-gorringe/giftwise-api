<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

checkAuth();

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

//check that the uid exists
if(!$GLOBALS['database']->has('account', [
    'account_uid' => $uid
])){
    http_response_code(401);
    exit;
}

$wishlist = $GLOBALS['database']->get('account', '*', [
    'account_uid' => $uid
]);
$items = $GLOBALS['database']->select('item', '*', [
    'account_id' => $wishlist['account_id']
]);

$response->wishlist = $wishlist;
$response->items = $items;

return json_encode($response);