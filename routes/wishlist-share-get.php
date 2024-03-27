<?php
$response = (object)[];
$response->valid = true;

checkAuth();

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

//if no uid return 400
if(empty($uid)){
    http_response_code(400);
    $response->valid = false;
    $response->error = "Please enter a valid wishlist id.";
    return json_encode($response);
    exit;
}

//check that the uid exists
if(!$GLOBALS['database']->has('account', [
    'account_uid' => $uid
])){
    http_response_code(400);
    $response->valid = false;
    $response->error = "The wishlist does not exist.";
    return json_encode($response);
    exit;
}

$account = $GLOBALS['database']->get('account', '*', [
    'account_uid' => $uid
]);
if($account['user_id'] != $user_id){
    http_response_code(401);
    $response->valid = false;
    $response->error = "You don't have permission to do this.";
    return json_encode($response);
    exit;
}

if($account['is_private'] == 1){
    http_response_code(403);
    $response->valid = false;
    $response->error = "This wishlist is private.";
    return json_encode($response);
    exit;
}

if($response->valid === true){
    //check if account_share already has a record with the account_id
    if($GLOBALS['database']->has('account_share', [
        'account_id' => $account['account_id']
    ])){
        //get the share_code from the existing record
        $share_code = $GLOBALS['database']->get('account_share', 'share_code', [
            'account_id' => $account['account_id']
        ]);
    }else{
        //create a new share_code
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $share_code = randomString(6, $characters);
        while ($GLOBALS['database']->has('account_share', ['share_code' => $share_code])) {
            $share_code = randomString(6, $characters);
        }
        //insert the new share_code
        $GLOBALS['database']->insert('account_share', [
            'account_id' => $account['account_id'],
            'share_code' => $share_code
        ]);
    }

    $link_accounts = $GLOBALS['database']->select('account_link', [
        '[>]account' => ['user_id' => 'user_id', 'AND' => ['is_primary' => 1]], // Join condition with additional filter for primary account
        '[>]image' => ['account.image_id' => 'image_id'] // Join with image table based on account's image_id
    ], [
        'account_link.account_link_uid',
        'account.account_name',
        'image.image_uid',
    ], [
        'account_link.account_id' => $account['account_id'] // Your filtering condition
    ]);
    foreach ($link_accounts as $key => $link_account) {
        if($link_account['image_uid'] == null){
            $link_accounts[$key]['image_url'] = null;
        }else{
            $link_accounts[$key]['image_url'] = 'https://r2.giftwise.app/'.$link_account['image_uid'].'_100.jpg';
        }
    }

    $response->link_accounts = $link_accounts;
    $response->share_code = $share_code;
    return json_encode($response);
    exit;
}
