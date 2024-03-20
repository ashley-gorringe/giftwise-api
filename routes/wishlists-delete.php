<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

checkAuth();

//if empty uid return 400
if(empty($uid)){
    http_response_code(400);
    $response->valid = false;
    $response->error = "Please enter a valid item id.";
    return json_encode($response);
    exit;
}

//get the user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

//check that the uid exists
if(!$GLOBALS['database']->has('account', [
    'account_uid' => $uid
])){
    http_response_code(401);
    $response->valid = false;
    $response->error = "The wishlist you are trying to delete does not exist.";
    return json_encode($response);
    exit;
}

//check that the user is the owner of the item
$account = $GLOBALS['database']->get('account', '*', [
    'account_uid' => $uid
]);
if($account['user_id'] != $user_id){
    http_response_code(401);
    $response->valid = false;
    $response->error = "You don't have permission to delete this item.";
    return json_encode($response);
    exit;
}

//delete the item
$GLOBALS['database']->delete('account', [
    'account_uid' => $uid
]);

//delete the image
if($account['image_id'] != null){
    $image_uid = $GLOBALS['database']->get('image', 'image_uid', [
        'image_id' => $account['image_id']
    ]);
    $GLOBALS['database']->delete('image', [
        'image_id' => $account['image_id']
    ]);
    $GLOBALS['s3_client']->deleteObject([
        'Bucket' => 'giftwise',
        'Key' => $image_uid.'_500.jpg'
    ]);
    $GLOBALS['s3_client']->deleteObject([
        'Bucket' => 'giftwise',
        'Key' => $image_uid.'_300.jpg'
    ]);
    $GLOBALS['s3_client']->deleteObject([
        'Bucket' => 'giftwise',
        'Key' => $image_uid.'_100.jpg'
    ]);
}

$response->wishlists = getWishlists($user_id);

return json_encode($response);
?>
