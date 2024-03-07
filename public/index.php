<?php
//All traffic is redirected to this index.php file via the .htaccess file.

require_once dirname($_SERVER['DOCUMENT_ROOT']).'/execute.php';//Sets up the full PHP environment, loads in dependancies, functions, and checks to see if a user is signed in.
?>