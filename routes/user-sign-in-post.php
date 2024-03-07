<?php
$response = (object)[];

//Checks if the email is set
if(!isset($_POST['email'])){
    $response->error = 'Email is not set';
    return json_encode($response);
    exit;
}
//Checks if the email is valid
if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $response->error = 'Email is not valid';
    return json_encode($response);
    exit;
}
//Checks if the email is already in use
$email_count = $GLOBALS['database']->count('user', [
    'email' => $_POST['email']
]);
if($email_count < 1){
    $response->error = 'Email doesnt exist';
    return json_encode($response);
    exit;
}

//Checks if the password is set
if(!isset($_POST['password'])){
    $response->error = 'Password is not set';
    return json_encode($response);
    exit;
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

//Checks if the password is correct
if(!password_verify($_POST['password'], $user['password'])){
    $response->error = 'Password is incorrect';
    return json_encode($response);
    exit;
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

    $response->token = $token;
    $response->user = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'name_full' => $user['name_full'],
        'name_preferred' => $user['name_preferred'] ?? $user['name_full']
    ];

    return json_encode($response);
}