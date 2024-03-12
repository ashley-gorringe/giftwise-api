<?php
$response = (object)[];
$response->error_fields = (object)[];
$response->valid = true;

//Checks if the email is set
if(empty($_POST['email'])){
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
if($email_count < 1){
    $response->error_fields->email = 'This email address is not registered. Please try another one.';
    $response->valid = false;
}

//Checks if the password is set
if(empty($_POST['password'])){
    $response->error_fields->password = 'Please enter your password.';
    $response->valid = false;
}

//get the user from the email
$user = $GLOBALS['database']->get('user',[
    'user_id',
    'email',
    'password',
    'name_full',
    'name_preferred'
], [
    'email' => $_POST['email']
]);


if($response->valid){
    if(!password_verify($_POST['password'], $user['password'])){
        $response->error_fields->password = 'Your password is incorrect. Please try again.';
        $response->error = "There's something wrong with your submission. Please check the fields and try again.";
    }else{
        //Creates the token
        $token = bin2hex(random_bytes(18));
        while ($GLOBALS['database']->has('login_token', ['token' => $token])) {
            $token = bin2hex(random_bytes(18));
        }

        $GLOBALS['database']->insert('login_token', [
            'user_id' => $user['user_id'],
            'token' => $token
        ]);

        $primary_account = $GLOBALS['database']->get('account','account_uid', [
            'user_id' => $user['user_id'],
            'is_primary' => 1
        ]);

        $accounts = $GLOBALS['database']->select('account','*', [
            'user_id' => $user['user_id'],
            'is_primary' => 0
        ]);

        $response->token = $token;
        $response->user = [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'name_full' => $user['name_full'],
            'name_preferred' => $user['name_preferred'] ?? $user['name_full'],
            'primary_account' => $primary_account,
        ];
        $response->user['accounts'] = $accounts;
    }
}else{
    $response->error = "There's something wrong with your submission. Please check the fields and try again.";
}

return json_encode($response);