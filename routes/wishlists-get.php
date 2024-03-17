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

foreach($items as $key => $item){
    if($item['image_id'] != null){
        $image_uid = $GLOBALS['database']->get('image', 'image_uid', [
            'image_id' => $item['image_id']
        ]);
        $images = array(
            '1000' => 'https://r2.giftwise.app/'.$image_uid.'_1000.jpg',
            '500' => 'https://r2.giftwise.app/'.$image_uid.'_500.jpg',
            '300' => 'https://r2.giftwise.app/'.$image_uid.'_300.jpg'
        );
        $items[$key]['images'] = $images;
    }else{
        $items[$key]['images'] = null;
    }

    //if value is not null, get the value convert it from cents to dollars
    if($item['value'] != null){
        $items[$key]['value'] = $item['value']/100;
        //format the value to 2 decimal places and commas
        $items[$key]['value'] = number_format($items[$key]['value'], 2, '.', ',');
    }

}

$response->wishlist = $wishlist;
$response->items = $items;

return json_encode($response);