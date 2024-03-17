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

return json_encode((object)[
    'user' => getUserData($user_id)
]);
exit;
?>