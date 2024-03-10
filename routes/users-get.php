<?php

if(isset($_GET['email'])){
    $email = $_GET['email'];
    $user_count = $GLOBALS['database']->count('user', [
        'email' => $email
    ]);

    if($user_count > 0){
        return json_encode((object)[
            'user_count' => $user_count
        ]);
    }else{
        return json_encode((object)[
            'user_count' => $user_count
        ]);
    }
}else{
    http_response_code(400);
}