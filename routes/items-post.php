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

        /* //get product detials from the link
        $info = $GLOBALS['embed']->get($url);
        $metadata = $info->getMetas();

        $response->product = [
            'title' => $info->title,
            'description' => $info->description,
            'url' => $info->url,
            'image' => $info->image,
            'metadata' => $metadata->all()
        ]; */
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

        if($_FILES){
            if($_FILES['image']['error'] != 0){
                $response->error = $_FILES['image']['error'];
                return json_encode($response);
                exit;
            }
            //image name current timestamp plus random string
            $image_tempname = bin2hex(random_bytes(8));

            $image = new Bulletproof\Image($_FILES['image']);
            $image->setName($image_tempname);
            $image->setSize(0, 4000000);
            $image->setMime(array('jpeg', 'jpg', 'png'));

            $uploadDir = dirname($_SERVER['DOCUMENT_ROOT']).'/uploads';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                $response->error = "Upload directory does not exist or is not writable.";
                return json_encode($response);
                exit;
            }
            $image->setStorage($uploadDir);

            $upload = $image->upload();
            if(!$upload){
                $response->error = "There was an error uploading your image. Please try again.";
                return json_encode($response);
                exit;
            }else{
                $image_uid = bin2hex(random_bytes(18));
                while ($GLOBALS['database']->has('image', ['image_uid' => $image_uid])) {
                    $image_uid = bin2hex(random_bytes(18));
                }
                $GLOBALS['database']->insert('image',[
                    'image_uid'=>$image_uid,
                ]);
                $image_id = $GLOBALS['database']->id();

                $image_file = $image->getName().'.'.$image->getMime();

                resizeImage($uploadDir.'/'.$image_file, 1000, $uploadDir.'/'.$image_uid.'_1000.jpg');
                resizeImage($uploadDir.'/'.$image_file, 500, $uploadDir.'/'.$image_uid.'_500.jpg');
                resizeImage($uploadDir.'/'.$image_file, 300, $uploadDir.'/'.$image_uid.'_300.jpg');
                unlink($uploadDir.'/'.$image_file);

                try {
                    $GLOBALS['s3_client']->putObject([
                        'Bucket' => 'giftwise',
                        'Key' => $image_uid.'_1000.jpg',
                        'Body' => fopen($uploadDir.'/'.$image_uid.'_1000.jpg', 'r'),
                        'ACL' => 'public-read',
                    ]);
                    unlink($uploadDir.'/'.$image_uid.'_1000.jpg');
                    $GLOBALS['s3_client']->putObject([
                        'Bucket' => 'giftwise',
                        'Key' => $image_uid.'_500.jpg',
                        'Body' => fopen($uploadDir.'/'.$image_uid.'_500.jpg', 'r'),
                        'ACL' => 'public-read',
                    ]);
                    unlink($uploadDir.'/'.$image_uid.'_500.jpg');
                    $GLOBALS['s3_client']->putObject([
                        'Bucket' => 'giftwise',
                        'Key' => $image_uid.'_300.jpg',
                        'Body' => fopen($uploadDir.'/'.$image_uid.'_300.jpg', 'r'),
                        'ACL' => 'public-read',
                    ]);
                    unlink($uploadDir.'/'.$image_uid.'_300.jpg');
        
                } catch (Aws\S3\Exception\S3Exception $e) {
                    //echo "There was an error uploading the file.\n";
                    $response->error = "There was an error uploading the file.";
                    echo json_encode($response);
                    exit;
                }
            }
        }else{
            $image_id = null;
        }

        $GLOBALS['database']->insert('item', [
            'item_uid' => $item_uid,
            'account_id' => $account['account_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_id' => $image_id
        ]);
            

        $response->item = [
            'item_uid' => $item_uid,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_id' => $image_id
        ];

        return json_encode($response);
    }else{
        $response->error = "There's something wrong with your submission. Please check the fields and try again.";
        return json_encode($response);
    }

}else{
    http_response_code(400);
}