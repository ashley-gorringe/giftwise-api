<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

checkAuth();

if($_GET['type'] == 'link'){
    if(empty($_POST['link'])){
        $response->error_fields->link = 'Please enter a link to the item.';
        $response->valid = false;
        $response->error = "There's something wrong with your submission. Please check the fields and try again.";
    }elseif(!filter_var($_POST['link'], FILTER_VALIDATE_URL)){
        $response->error_fields->link = 'Something is wrong with your link. Please try again.';
        $response->valid = false;
        $response->error = "There's something wrong with your submission. Please check the fields and try again.";
    }else{
        $url = $_POST['link'];
        $api_key = $_ENV['JSONLINK_API_KEY'];
        $request_url = "https://jsonlink.io/api/extract?url=$url&api_key=$api_key";
        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $curl_response = curl_exec($ch);
        if (curl_errno($ch)) {
            $response->error_fields->link = "We couldn't get the data from the link. Please try again.";
            $response->valid = false;
            $response->error = curl_error($ch);
        }else{
            $product_data = json_decode($curl_response);

            if(!empty($product_data->title)){
                $response->product = (object)[];
                $response->product->title = $product_data->title;
                $response->product->description = $product_data->description;
                $response->product->url = $product_data->url;
            }

        }
    }
    
    return json_encode($response);

}elseif($_GET['type'] == 'manual'){

    if(empty($_POST['wishlist'])){
        http_response_code(400);
        exit;
    }
    /* //get userid from token
    $user_id = $GLOBALS['database']->get('login_token', 'user_id', [
        'token' => $_GET['token']
    ]);
    //make sure that the user is the owner of the wishlist
    $account = $GLOBALS['database']->get('account', [
        'account_uid' => $_POST['wishlist'],
        'user_id' => $user_id
    ]);
    if($account == false){
        http_response_code(401);
        exit;
    } */

    //get user_id from the token
    $user_id = $GLOBALS['database']->get('login_token', 'user_id', [
        'token' => $_GET['token']
    ]);
    $account = $GLOBALS['database']->get('account','*', [
        'account_uid' => $_POST['wishlist'],
        'user_id' => $user_id
    ]);
    if($account == false){
        http_response_code(401);
        exit;
    }

    if(empty($_POST['title'])){
        $response->error_fields->title = 'Please enter a title for the item.';
        $response->valid = false;
    }

    if($response->valid){
        $item_uid = bin2hex(random_bytes(18));
        while ($GLOBALS['database']->has('item', ['item_uid' => $item_uid])) {
            $item_uid = bin2hex(random_bytes(18));
        }

        $GLOBALS['database']->insert('item', [
            'item_uid' => $item_uid,
            'account_id' => $account['account_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
        ]);

        $response->item = [
            'item_uid' => $item_uid,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
        ];

        return json_encode($response);
    }

}else{
    http_response_code(400);
}