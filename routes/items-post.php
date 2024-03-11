<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

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

}