<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

checkAuth();

$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

if(empty($_POST['share_code'])){
    $response->error_fields->share_code = 'Please enter a valid Share Code.';
    $response->valid = false;
}else{
    //check that share code is 6 digits numbers and uppercase letters
    if(!preg_match('/^[A-Z0-9]{6}$/', $_POST['share_code'])){
        $response->error_fields->share_code = 'Please enter a valid Share Code.';
        $response->valid = false;
    }else{
        $share_code = $_POST['share_code'];
    }
}

if($response->valid){

    //check if share code exists
    if(!$GLOBALS['database']->has('account_share', [
        'share_code' => $_POST['share_code']
    ])){
        $response->valid = false;
        $response->error_fields->share_code = 'Please enter a valid Share Code.';
        $response->error = "That Share Code does not exist.";
        return json_encode($response);
        exit;
    }

    //get the account_id from the share code
    $account_id = $GLOBALS['database']->get('account_share', 'account_id', [
        'share_code' => $_POST['share_code']
    ]);
    //get account details
    $account = $GLOBALS['database']->get('account', [
        '[>]image' => ['image_id' => 'image_id'],
    ],[
        'account.user_id',
        'account.account_uid',
        'account.account_name',
        'account.is_private',
        'image.image_uid'
    ],[
        'account_id' => $account_id
    ]);

    if($account['user_id'] == $user_id){
        $response->valid = false;
        $response->error_fields->share_code = "You can't follow your own wishlist.";
        $response->error = "You can't follow your own wishlist.";
        return json_encode($response);
        exit;
    }

    //check if the user is already following the wishlist
    if($GLOBALS['database']->has('account_link', [
        'account_id' => $account_id,
        'user_id' => $user_id
    ])){
        $response->valid = false;
        $response->error_fields->share_code = "You're already following this wishlist.";
        $response->error = "You're already following this wishlist.";
        return json_encode($response);
        exit;
    }

    $account_link_uid = bin2hex(random_bytes(18));
    while ($GLOBALS['database']->has('account_link', ['account_link_uid' => $account_link_uid])) {
        $account_link_uid = bin2hex(random_bytes(18));
    }

    //insert the record into account_link
    $GLOBALS['database']->insert('account_link', [
        'account_link_uid' => $account_link_uid,
        'account_id' => $account_id,
        'user_id' => $user_id
    ]);

    $response->account = [
        'account_link_uid' => $account_link_uid,
        'account_uid' => $account['account_uid'],
        'account_name' => $account['account_name'],
        'image' => $account['image_uid']
    ];
    return json_encode($response);
    exit;

}else{
    $response->error = "There's something wrong with your submission. Please check the fields and try again.";
    return json_encode($response);
    exit;
}

