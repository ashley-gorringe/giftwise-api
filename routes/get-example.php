<?php
use Ramsey\Uuid\Uuid;
$uuid = Uuid::uuid4();
return $uuid.' This route responds to requests with the GET method at the path /example';
?>