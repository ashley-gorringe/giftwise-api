<?php

function checkAuth(){
    if(!isset($_GET['token'])){
        http_response_code(400);
        exit;
    }else{
        $token_count = $GLOBALS['database']->count('login_token', [
            'token' => $_GET['token']
        ]);
        if($token_count == 0){
            http_response_code(401);
            exit;
        }
    }
}

function getUserData($user_id){
    $user = $GLOBALS['database']->get('user','*', [
        'user_id' => $user_id
    ]);
    $primary_account = $GLOBALS['database']->get('account','*', [
        'user_id' => $user_id,
        'is_primary' => 1
    ]);
    $image_uid = $GLOBALS['database']->get('image','image_uid', [
        'image_id' => $primary_account['image_id']
    ]);
    if($image_uid == null){
        $picture = null;
    }else{
        $picture = 'https://r2.giftwise.app/'.$image_uid.'_100.jpg';
    }

    $wishlists = getWishlists($user_id);

    $data = array(
        'wishlists' => $wishlists,
        'primary_account' => $primary_account['account_uid'],
        'name_preferred' => $user['name_preferred'],
        'email' => $user['email'],
        'picture' => $picture
    );
    return $data;
}

function getWishlists($user_id){
    $wishlists = $GLOBALS['database']->select('account', [
        '[>]image' => ['image_id' => 'image_id'],
    ], [
        'account.account_uid',
        'account.name_full',
        'image.image_uid'
    ], [
        'user_id' => $user_id,
        'is_primary' => 0
    ]);

    foreach ($wishlists as $key => $wishlist) {
        if($wishlist['image_uid'] == null){
            $wishlists[$key]['image_uid'] = null;
        }else{
            $wishlists[$key]['image_uid'] = 'https://r2.giftwise.app/'.$wishlist['image_uid'].'_100.jpg';
        }
    }

    return $wishlists;
}

function resizeImage($source, $width, $destination){
    $GLOBALS['imageManager']->make($source)->resize($width, null, function ($constraint) {
        $constraint->aspectRatio();
    })->save($destination);
}