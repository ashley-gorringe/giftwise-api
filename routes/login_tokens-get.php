<?php

//count the number of tokens in the database
$token_count = $GLOBALS['database']->count('login_token', [
    'token' => $token
]);
if($token_count == 0){
    return json_encode((object)[
        'error' => 'Token not found'
    ]);
    exit;
}

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $token
]);
//get the user from the user_id
$user = $GLOBALS['database']->get('user',[
    'user_id',
    'email',
    'name_full',
    'name_preferred'
], [
    'user_id' => $user_id
]);
//get the primary account from the user_id
$primary_account = $GLOBALS['database']->get('account','account_uid', [
    'user_id' => $user_id,
    'is_primary' => 1
]);
$user['primary_account'] = $primary_account;
return json_encode((object)[
    'user' => $user
]);
exit;
?>