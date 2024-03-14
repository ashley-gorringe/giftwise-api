<?php

function checkAuth(){
    if(!isset($_GET['token'])){
        http_response_code(400);
        exit;
    }else{
        $token_count = $GLOBALS['database']->count('login_token', [
            'token' => $_GET['token']
        ]);
        if($token_count == 0){
            http_response_code(401);
            exit;
        }
    }
}

function resizeImage($source, $width, $destination){
    $GLOBALS['imageManager']->make($source)->resize($width, null, function ($constraint) {
        $constraint->aspectRatio();
    })->save($destination);
}