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
if($email_count > 0){
    $response->error = 'Email is already in use';
    return json_encode($response);
    exit;
}
//Checks if the password is set
if(!isset($_POST['password'])){
    $response->error = 'Password is not set';
    return json_encode($response);
    exit;
}
//Checks if the password matches password_re
if($_POST['password'] != $_POST['password_re']){
    $response->error = 'Passwords do not match';
    return json_encode($response);
    exit;
}

//Checks if name_full is set
if(!isset($_POST['name_full'])){
    $response->error = 'Full name is not set';
    return json_encode($response);
    exit;
}


//Creates the user
$GLOBALS['database']->insert('user', [
    'email' => $_POST['email'],
    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
    'name_full' => $_POST['name_full'],
    'name_preferred' => $_POST['name_preferred'] ?? ''
]);
$user_id = $GLOBALS['database']->id();

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
    'name_preferred' => $_POST['name_preferred'] ?? $_POST['name_full']
];

return json_encode($response);
?>