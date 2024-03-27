<?php
$response = (object)[];
$response->valid = true;

checkAuth();

//if empty uid return 400
if(empty($uid)){
    http_response_code(400);
    $response->valid = false;
    $response->error = "An error occured. Please try again.";
    return json_encode($response);
    exit;
}

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

//check that the uid exists
if(!$GLOBALS['database']->has('account_link', [
    'account_link_uid' => $uid
])){
    http_response_code(400);
    $response->valid = false;
    $response->error = "An error occured. Please try again.";
    return json_encode($response);
    exit;
}

//get the details of the account_link
$account_link = $GLOBALS['database']->get('account_link', '*', [
    'account_link_uid' => $uid
]);

//get the user_id of the account
$account_user_id = $GLOBALS['database']->get('account', 'user_id', [
    'account_id' => $account_link['account_id']
]);

$authorised = false;
//check that the user is the owner of the item
if($account_user_id == $user_id){
    $authorised = true;
}else if($account_link['user_id'] == $user_id){
    $authorised = true;
}else{
    $authorised = false;
}

if(!$authorised){
    http_response_code(401);
    $response->valid = false;
    $response->error = "An error occured. Please try again.";
    return json_encode($response);
    exit;
}else{
    //delete the item
    $GLOBALS['database']->delete('account_link', [
        'account_link_uid' => $uid
    ]);



    return json_encode($response);
    exit;
}

