<?php
$email_count = $GLOBALS['database']->count('user', [
    'email' => $email
]);
return json_encode($email_count);
?>