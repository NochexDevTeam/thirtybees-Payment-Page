<?php
/*
Plugin Name: Nochex Payment Gateway for Thirty bees
Description: Accept Nochex Payments, orders are updated using APC.
Version: 2.0
License: GPL2
*/
/* Includes information from two files, config.inc.php and nochex.php */
include(dirname(__FILE__).'/../../config/config.inc.php');
// Include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/nochex.php');
//--- This includes/gets a file which has a write function to nochex_debug.txt ---//
require(dirname(__FILE__).'/writeFunction.php');
//--- Creates a new instance of the nochexDebug class ---//
$nochexDebug = new nochexDebug();
		
// VARIABLES

if (!isset($_POST)) $_POST = &$HTTP_POST_VARS;
foreach ($_POST AS $key => $value) {
$values[] = $key."=".urlencode($value);
}
$work_string = @implode("&", $values);

$url = "https://www.nochex.com/apcnet/apc.aspx";
$ch = curl_init ();
curl_setopt ($ch, CURLOPT_URL, $url);
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $work_string);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
$output = curl_exec ($ch);
curl_close ($ch);

$response = preg_replace ("'Content-type: text/plain'si","",$output);
  
$responses = "Payment Accepted - APC ". $response .". Transaction Status - ".$_POST["status"] ;

$transaction_id = $_POST["transaction_id"];
$currencyID = $_REQUEST["cIY"];

//--- Creates a new instances of the nochex class and gets the variable for the currency ---//
$nochex = new nochex(); 

$extras = array("transaction_id" => $transaction_id);
$customer->secure_key = $_POST["custom"];

/* If statement which checks the apc status of an order */
if ($response=="AUTHORISED") 
{
	$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_PAYMENT'), $_POST["amount"], $nochex->displayName,$responses,$extras, $currencyID,false, $customer->secure_key);
	$responseMessage = "APC Response .... " . $response . " .... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". _PS_OS_PAYMENT_ . ". Amount: ". $_POST["amount"]. ". Display name: ". $nochex->displayName. ". CurrencyID: ". $currencyID;
	
} else {
	$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_ERROR'), $_POST["amount"], $nochex->displayName,$responses,$extras, $currencyID, $customer->secure_key);
	$responseMessage = "APC Response .... " . $response . " .... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". _PS_OS_PAYMENT_ . "... Amount: ". $_POST["amount"]. "... Display name: ". $nochex->displayName. "... CurrencyID: ". $currencyID;
} 
	//--- The variable with the APC response stored is sent to the new instance class with a function that writes to nochex_debug.txt ---//
	$nochexDebug->writeDebug($responseMessage);
?>