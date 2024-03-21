<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

//Checks if the email is set
if(!isset($_POST['email'])){
    $response->error_fields->email = 'Please enter your email address.';
    $response->valid = false;
}
//Checks if the email is valid
if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $response->error_fields->email = 'Something is wrong with your email address. Please try again.';
    $response->valid = false;
}
//Checks if the email is already in use
$email_count = $GLOBALS['database']->count('user', [
    'email' => $_POST['email']
]);
if($email_count > 0){
    $response->error_fields->email = 'This email address is already in use. Please try another one.';
    $response->valid = false;
}
//Checks if the password is set
if(empty($_POST['password'])){
    $response->error_fields->password = 'Please enter a password.';
    $response->valid = false;
}
//Checks if the password matches password_re
if($_POST['password'] != $_POST['password_re']){
    $response->error_fields->password_re = 'The passwords do not match. Please try again.';
    $response->valid = false;
}

//Checks if name_full is set
if(empty($_POST['name_full'])){
    $response->error_fields->name_full = 'Please enter your full name.';
    $response->valid = false;
}

if($response->valid){

    if(empty($_POST['name_preferred'])){
        $_POST['name_preferred'] = $_POST['name_full'];
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
        $image_uid = null;
    }


    //Creates the user
    $GLOBALS['database']->insert('user', [
        'email' => $_POST['email'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'name_preferred' => $_POST['name_preferred'],
    ]);
    $user_id = $GLOBALS['database']->id();

    $account_uid = bin2hex(random_bytes(18));
    while ($GLOBALS['database']->has('account', ['account_uid' => $account_uid])) {
        $account_uid = bin2hex(random_bytes(18));
    }
    $GLOBALS['database']->insert('account', [
        'user_id' => $user_id,
        'account_uid' => $account_uid,
        'is_primary' => 1,
        'is_private' => 0,
        'account_name' => $_POST['name_full'],
        'image_id' => $image_id,
    ]);

    //Creates the token
    $token = bin2hex(random_bytes(18));
    while ($GLOBALS['database']->has('login_token', ['token' => $token])) {
        $token = bin2hex(random_bytes(18));
    }

    $GLOBALS['database']->insert('login_token', [
        'user_id' => $user_id,
        'token' => $token
    ]);

    $response->token = $token;
    $response->user = getUserData($user_id);
}else{
    $response->error = "There's something wrong with your submission. Please check the fields and try again.";
}

return json_encode($response);
?>