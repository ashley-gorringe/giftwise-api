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
    //Creates the user
    $GLOBALS['database']->insert('user', [
        'email' => $_POST['email'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'name_full' => $_POST['name_full'],
        'name_preferred' => $_POST['name_preferred'] ?? ''
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
        'name_full' => $_POST['name_full'],
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
    $response->user = [
        'user_id' => $user_id,
        'email' => $_POST['email'],
        'name_full' => $_POST['name_full'],
        'name_preferred' => $_POST['name_preferred'] ?? $_POST['name_full'],
        'primary_account' => $account_uid
    ];
}else{
    $response->error = "There's something wrong with your submission. Please check the fields and try again.";
}

return json_encode($response);
?>