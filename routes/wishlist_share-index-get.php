<?php

$response = (object)[];

checkAuth();

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

$link_accounts = $GLOBALS['database']->select('account_link', [
    '[>]account' => ['account_id' => 'account_id'], // Join condition with additional filter for primary account
    '[>]image' => ['account.image_id' => 'image_id'] // Join with image table based on account's image_id
], [
    'account_link.account_link_uid',
    'account.account_uid',
    'account.account_name',
    'image.image_uid',
], [
    'account_link.user_id' => $user_id // Your filtering condition
]);
foreach ($link_accounts as $key => $link_account) {
    if($link_account['image_uid'] == null){
        $link_accounts[$key]['image_url'] = null;
    }else{
        $link_accounts[$key]['image_url'] = 'https://r2.giftwise.app/'.$link_account['image_uid'].'_100.jpg';
    }
}

$response->link_accounts = $link_accounts;

return json_encode($response);