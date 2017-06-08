<?php
require_once('vendor/autoload.php');
require_once('settings.php');

//$location = "http://127.0.0.1:9000";
$location = "https://pay.dazser.com";

/*
 * First, let's validate and sanatize the data!
 * Here is the info I need:
 * email, invoice, invoice_amount, charged_amount, payment-brand,
 * last4, client_ip, and created;
 */

$return['message'] = "Your account has been charged and a receipt has been emailed to you.
  You may now close this window.
  <br/><br/>Thank you for your business!";

function sanitize($data) {
  // This function sanatizes the data
  return trim(stripslashes(htmlspecialchars($data)));
}

function returnHome($result){
  $result['message'] .= "<br/>Your account has not been charged.<br/>
    Please return to <a href='https://pay.dazser.com'>https://pay.dazser.com</a> to retry.";
  header("Location: $location/receipt.html?r=".base64_encode(json_encode($result)));
  die;
}

$email = sanitize($_POST["email"]);
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $return['message'] = "Invalid email format";
  returnHome($return);
}

$invoice = sanitize($_POST["invoice"]);
//Let all invoice text go through :(
//if(!preg_match("/\b[1-6]{1}-[0-9]{5}\b/",$invoice)){
//  $return['message'] = "Invalid Invoice ID format";
//  returnHome($return);
//}

$invoice_amount = filter_var(sanitize($_POST["amount"]), FILTER_VALIDATE_FLOAT);
if($invoice_amount <= 0){
  $return['message'] = "Invoice Amount too low<br/>
    Please enter an amount greater than zero.";
    returnHome($return);
}
if($invoice_amount > 1000){
  $return['message'] = "Invoice Amount too high<br/>
    At this time, we can only accept payments up to $1000 online.";
    returnHome($return);
}

$invoice_amount_in_cents = filter_var($invoice_amount * 100, FILTER_VALIDATE_INT);

//No validation necessary
$charged_amount_in_cents = $invoice_amount_in_cents + 1500;
$_POST["stripeToken"] = json_decode($_POST["stripeToken"], true);
$payment_brand = sanitize($_POST["stripeToken"]["card"]["brand"]);
$last4 = filter_var(sanitize($_POST["stripeToken"]["card"]["last4"]), FILTER_VALIDATE_INT);

$client_ip = sanitize($_POST["stripeToken"]["client_ip"]);
if(!filter_var($client_ip, FILTER_VALIDATE_IP)) {
  $return['message'] = "Invalid IP Address";
  returnHome($return);
}

$created = filter_var(sanitize($_POST["stripeToken"]["created"]), FILTER_VALIDATE_INT);

//Second, let's connect to my database & insert the row
$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS, 'global');

if( $db->connect_errno > 0 ) {
  $return['message'] = "Unable to connect to database<br/>
    Error: " . $db->connect_error;
    returnHome($return);
}

//Create the statement
$insertSql = "INSERT INTO `stripe_charges` (`email`,`invoice`,
  `invoice_amount`,`charged_amount`,`payment-brand`,
  `last4`,`client_ip`, `created`) VALUES (?,?,?,?,?,?,
  INET_ATON(?),FROM_UNIXTIME(?))";

if($insert = $db->prepare($insertSql)){
  //Bind the parameters
  if(!$insert->bind_param('ssddsisi', $email, $invoice, $invoice_amount_in_cents,
    $charged_amount_in_cents, $payment_brand, $last4, $client_ip,
    $created)){
    //if bind_param fails
    $return['message'] = $db->error;
    returnHome($return);
  }

  //Execute the statement
  if(!$insert->execute()){
    $return['message'] = $db->error;
    returnHome($return);
  }

  //Get the ID
  $inserted_id = $insert->insert_id;

  //Free the results
  $insert->free_result();

} else {
  //Insert Failed!
  $return['message'] = $db->error;
  returnHome($return);
}

//Third, let's talk to Stripe to charge the card
//Set my secret key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$token = $_POST['stripeToken']['id'];

//Let's CHARGE THE CARD!
try {
  $charge = \Stripe\Charge::create(array(
    "amount"      =>  $charged_amount_in_cents, //Charge in WHOLE CENTS!
    "currency"    =>  "usd",
    "source"      =>  $token,
    "description" =>  "Jani-King Cleaning Services - ".html_entity_decode($invoice)." - includes $15 fee",
    "receipt_email" =>  $email,
    "metadata"    =>  array(
                        "invoice" =>  $invoice,
                        "email"   =>  $email
                      )
  ));
} catch(\Stripe\Error\Card $e) {
  // Since it's a decline, \Stripe\Error\Card will be caught
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\RateLimit $e) {
  // Too many requests made to the API too quickly
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\InvalidRequest $e) {
  // Invalid parameters were supplied to Stripe's API
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\Authentication $e) {
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\ApiConnection $e) {
  // Network communication with Stripe failed
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\Base $e) {
  // Display a very generic error to the user, and maybe send
  // yourself an email
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (Exception $e) {
  // Something else happened, completely unrelated to Stripe
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
}

//THE CARD HAS BEEN CHARGED!

//Finally, let's update the row in my db with more info from Stripe!
$updateSql = "UPDATE `stripe_charges` SET
  `transaction_id`=?, `charged`=FROM_UNIXTIME(?) WHERE `id`=?";
if($update = $db->prepare($updateSql)){
  $transaction_id = $charge["id"];
  $charged = $charge["created"];

  $update->bind_param('sii',$transaction_id,$charged,$inserted_id);

  $update->execute();

  $update->free_result();
} else {
  //The UPDATE failed :(
  $return['message'] .= $db->error;
}

$db->close();

//Send to receipt
header("Location: $location/receipt.html?r=".base64_encode(json_encode($return)));
die;
