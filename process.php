<?php
require_once('vendor/autoload.php');
require_once('settings.php');

/*
 * First, let's validate and sanatize the data!
 * Here is the info I need:
 * email, invoice, invoice_amount, charged_amount, payment-type, payment-brand,
 * last4, client_ip, and created;
 */

function test_input($data) {
  // This function sanatizes the data
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

$email = test_input($_POST["email"]);
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die("Invalid email format");
}

$invoice = test_input($_POST["invoice"]);
if(!preg_match("/\b[1-6]{1}-[0-9]{5}\b/",$invoice)){
  die("Invalid Invoice ID format");
}

$invoice_amount = filter_var(test_input($_POST["amount"]), FILTER_VALIDATE_FLOAT);
if($invoice_amount <= 0){
  die("Invoice Amount too low");
}
if($invoice_amount > 750){
  die("Invoice Amount too high");
}

$invoice_amount_in_cents = filter_var($invoice_amount * 100, FILTER_VALIDATE_INT);

//No validation necessary
$charged_amount_in_cents = $invoice_amount_in_cents + 1500;
$_POST["stripeResponse"] = json_decode($_POST["stripeResponse"], true);
$payment_type = test_input($_POST["payment-type"]);
$payment_brand = test_input($_POST["stripeResponse"]["card"]["brand"]);
$last4 = filter_var(test_input($_POST["stripeResponse"]["card"]["last4"]), FILTER_VALIDATE_INT);

$client_ip = test_input($_POST["stripeResponse"]["client_ip"]);
if(!filter_var($client_ip, FILTER_VALIDATE_IP)) {
  die("Invalid IP Address");
}

$created = filter_var(test_input($_POST["stripeResponse"]["created"]), FILTER_VALIDATE_INT);

//Second, let's connect to my database & insert the row
$db = new mysqli('localhost', $mysqlUser, $mysqlPass, 'global');

if( $db->connect_errno > 0 ) {
  die('Unable to connect to database ['. $db->connect_error .']');
}

//Create the statement
$insert = $db->prepare("INSERT INTO `stripe_charges` (`email`,`invoice`,
  `invoice_amount`,`charged_amount`,`payment-type`,`payment-brand`,
  `last4`,`client_ip`, `created`) VALUES (?,?,?,?,?,?,?,
  INET_ATON(?),FROM_UNIXTIME(?))");

//Bind the parameters
$insert->bind_param('ssddssisi', $email, $invoice, $invoice_amount_in_cents,
  $charged_amount_in_cents, $payment_type, $payment_brand, $last4, $client_ip,
  $created);

//Execute the statement
$insert->execute();

//Get the ID
$inserted_id = $insert->insert_id;

//Free the results
$insert->free_result();

//Third, let's talk to Stripe to charge the card
//Set my secret key
\Stripe\Stripe::setApiKey($stripeSecretKey);

$token = $_POST['stripeToken'];

try {
  $charge = \Stripe\Charge::create(array(
    "amount"      =>  $charged_amount_in_cents, //Charge in WHOLE CENTS!
    "currency"    =>  "usd",
    "source"      =>  $token,
    "description" =>  "Jani-King Cleaning Services - ".$invoice,
    "receipt_email" =>  $email,
    "metadata"    =>  array(
                        "invoice" =>  $invoice,
                        "email"   =>  $email
                      )
  ));
} catch(\Stripe\Error\Card $e) {
  echo json_encode($e);
  die();
}

//Finally, let's update the row in my db with more info from Stripe!
$update = $db->prepare("UPDATE `stripe_charges` SET
  `transaction_id`=?, `charged`=FROM_UNIXTIME(?) WHERE `id`=?");

echo json_encode($db->error);
$transaction_id = $charge["id"];
$charged = $charge["created"];

$update->bind_param('sii',$transaction_id,$charged,$inserted_id);

$update->execute();

$update->free_result();
$db->close();
