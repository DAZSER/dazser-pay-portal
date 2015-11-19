<pre>
<?php
require_once('vendor/autoload.php');
require_once('settings.php');


$fullResponse = $_REQUEST;

$fullResponse['stripeResponse'] = json_decode($fullResponse['stripeResponse']);

echo json_encode($fullResponse);
?>
</pre>
