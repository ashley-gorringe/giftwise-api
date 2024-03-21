<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

checkAuth();

//get user_id from the token
$user_id = $GLOBALS['database']->get('login_token', 'user_id', [
    'token' => $_GET['token']
]);

if(empty($_POST['account_name'])){
    $response->error_fields->title = 'Please enter a Full Name.';
    $response->valid = false;
}

if(!empty($_POST['who_for'])){
    if($_POST['who_for'] == 'myself'){
        $is_private = 1;
    }elseif($_POST['who_for'] == 'another'){
        $is_private = 0;
    }else{
        $response->valid = false;
    }
}

if($response->valid){
    $wishlist_uid = bin2hex(random_bytes(18));
    while ($GLOBALS['database']->has('account', ['account_uid' => $wishlist_uid])) {
        $wishlist_uid = bin2hex(random_bytes(18));
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

            resizeImage($uploadDir.'/'.$image_file, 500, $uploadDir.'/'.$image_uid.'_500.jpg');
            resizeImage($uploadDir.'/'.$image_file, 300, $uploadDir.'/'.$image_uid.'_300.jpg');
            resizeImage($uploadDir.'/'.$image_file, 300, $uploadDir.'/'.$image_uid.'_100.jpg');
            unlink($uploadDir.'/'.$image_file);

            try {
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
                $GLOBALS['s3_client']->putObject([
                    'Bucket' => 'giftwise',
                    'Key' => $image_uid.'_100.jpg',
                    'Body' => fopen($uploadDir.'/'.$image_uid.'_100.jpg', 'r'),
                    'ACL' => 'public-read',
                ]);
                unlink($uploadDir.'/'.$image_uid.'_100.jpg');
    
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

    $GLOBALS['database']->insert('account', [
        'user_id' => $user_id,
        'account_uid' => $wishlist_uid,
        'is_primary' => 0,
        'is_private' => $is_private,
        'account_name' => $_POST['account_name'],
        'image_id' => $image_id
    ]);

    $response->account_uid = $wishlist_uid;
    $response->is_private = $is_private;

    $response->wishlists = getWishlists($user_id);

    return json_encode($response);
}else{
    $response->error = "There's something wrong with your submission. Please check the fields and try again.";
    return json_encode($response);
}