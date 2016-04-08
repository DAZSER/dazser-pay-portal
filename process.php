<?php
require_once('vendor/autoload.php');
require_once('settings.php');

/*
 * First, let's validate and sanatize the data!
 * Here is the info I need:
 * email, invoice, invoice_amount, charged_amount, payment-type, payment-brand,
 * last4, client_ip, and created;
 */

 $return['message'] = "Your account has been charged and a receipt has been
    emailed to you. You may now close this window.
    <br/><br/>Thank you for your business!";

function sanitize($data) {
  // This function sanatizes the data
  return trim(stripslashes(htmlspecialchars($data)));
}

function returnHome($result){
  $result['message'] .= "<br/>Your account has not been charged.<br/>
    Please return to <a href='https://pay.dazser.com'>https://pay.dazser.com</a>
     to retry.";
  //header('Location: http://athena:9000/receipt.html?r='.
  header('Location: https://pay.dazser.com/receipt.html?r='.
    base64_encode(json_encode($result)));
  die;
}

$email = sanitize($_POST["email"]);
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $return['message'] = "Invalid email format";
  returnHome($return);
}

$invoice = sanitize($_POST["invoice"]);
if(!preg_match("/\b[1-6]{1}-[0-9]{5}\b/",$invoice)){
  $return['message'] = "Invalid Invoice ID format";
  returnHome($return);
}

$invoice_amount = filter_var(sanitize($_POST["amount"]), FILTER_VALIDATE_FLOAT);
if($invoice_amount <= 0){
  $return['message'] = "Invoice Amount too low<br/>
    Please enter an amount greater than zero.";
    returnHome($return);
}
if($invoice_amount > 750){
  $return['message'] = "Invoice Amount too high<br/>
    At this time, we can only accept Invoices up to $750 online.";
    returnHome($return);
}

$invoice_amount_in_cents = filter_var($invoice_amount * 100, FILTER_VALIDATE_INT);

//No validation necessary
$charged_amount_in_cents = $invoice_amount_in_cents + 1500;
$_POST["stripeResponse"] = json_decode($_POST["stripeResponse"], true);
$payment_type = sanitize($_POST["payment-type"]);
$payment_brand = sanitize($_POST["stripeResponse"]["card"]["brand"]);
$last4 = filter_var(sanitize($_POST["stripeResponse"]["card"]["last4"]), FILTER_VALIDATE_INT);

$client_ip = sanitize($_POST["stripeResponse"]["client_ip"]);
if(!filter_var($client_ip, FILTER_VALIDATE_IP)) {
  $return['message'] = "Invalid IP Address";
  returnHome($return);
}

$created = filter_var(sanitize($_POST["stripeResponse"]["created"]), FILTER_VALIDATE_INT);

//Second, let's connect to my database & insert the row
$db = new mysqli('localhost', MYSQL_USER, MYSQL_PASS, 'global');

if( $db->connect_errno > 0 ) {
  $return['message'] = "Unable to connect to database<br/>
    Error: " . $db->connect_error;
    returnHome($return);
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
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

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
  // Since it's a decline, \Stripe\Error\Card will be caught
  $body = $e->getJsonBody();
  $error  = $body['error'];
  returnHome($error);
} catch (\Stripe\Error\RateLimit $e) {
  // Too many requests made to the API too quickly
} catch (\Stripe\Error\InvalidRequest $e) {
  // Invalid parameters were supplied to Stripe's API
} catch (\Stripe\Error\Authentication $e) {
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
} catch (\Stripe\Error\ApiConnection $e) {
  // Network communication with Stripe failed
} catch (\Stripe\Error\Base $e) {
  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (Exception $e) {
  // Something else happened, completely unrelated to Stripe
}

//Finally, let's update the row in my db with more info from Stripe!
$update = $db->prepare("UPDATE `stripe_charges` SET
  `transaction_id`=?, `charged`=FROM_UNIXTIME(?) WHERE `id`=?");

$transaction_id = $charge["id"];
$charged = $charge["created"];

$update->bind_param('sii',$transaction_id,$charged,$inserted_id);

$update->execute();

$update->free_result();
$db->close();

//Send to receipt
//header('Location: http://athena:9000/receipt.html?r='.base64_encode($return));
header('Location: https://pay.dazser.com/receipt.html?r='.
  base64_encode(json_encode($return)));
die;
