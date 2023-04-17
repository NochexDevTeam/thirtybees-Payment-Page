<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Nochex
*  @copyright  2007-2019 Nochex
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*  Plugin Name: Nochex Payment Gateway for Prestashop
*  Description: Accept Nochex Payments, orders are updated using APC.
*  Version: 2.0
*  License: GPL2
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/nochex.php');
	/** Creates a new instance of the nochexDebug class */
	$nochexDebug = new nochex();
	$values = "";
	$customer = "";
	// VARIABLES
	$nochex = new nochex();
	$work_string = http_build_query($_POST);

if(isset($_POST["optional_2"]) == "Enabled"){

	$url = "https://secure.nochex.com/callback/callback.aspx";

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, true);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $work_string);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$output = curl_exec ($ch);
	curl_close ($ch);

	if ($output == "AUTHORISED") {	 
		$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_PAYMENT'), $_POST["amount"], $nochex->displayName, $responseMessage, ["transaction_id" => $_POST["transaction_id"]], (int)$_REQUEST["cIY"], false, $_POST["optional_1"]);
        $responseAuthorisedMessage= "APC Response.... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". Configuration::get('PS_OS_PAYMENT') . ". Amount: ". $_POST["amount"]. ". Display name: ". $nochex->displayName. ". CurrencyID: ". $_REQUEST["cIY"];
        $nochexDebug->writeDebug($responseAuthorisedMessage);
    } else {
		$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_ERROR'), $_POST["amount"], $nochex->displayName, $responseMessage, ["transaction_id" => $_POST["transaction_id"]], (int)$_REQUEST["cIY"], false, $_POST["optional_1"]);
        $responseAuthorisedMessage= "APC Response.... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". Configuration::get('PS_OS_PAYMENT') . ". Amount: ". $_POST["amount"]. ". Display name: ". $nochex->displayName. ". CurrencyID: ". $_REQUEST["cIY"];
        $nochexDebug->writeDebug($responseAuthorisedMessage);
    }

} else { 

    $url = "https://secure.nochex.com/apc/apc.aspx";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $work_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch); 
	curl_close($ch); 
	
    $responseMessage= "APC Response.... " . $response;
    
	if ($output == "AUTHORISED") {	 
		$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_PAYMENT'), $_POST["amount"], $nochex->displayName, $responseMessage, ["transaction_id" => $_POST["transaction_id"]], (int)$_REQUEST["cIY"], false, $_POST["custom"]);
        $responseAuthorisedMessage= "APC Response.... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". Configuration::get('PS_OS_PAYMENT') . ". Amount: ". $_POST["amount"]. ". Display name: ". $nochex->displayName. ". CurrencyID: ". $_REQUEST["cIY"];
        $nochexDebug->writeDebug($responseAuthorisedMessage);
    } else {
		$nochex->validateOrder($_POST["order_id"], Configuration::get('PS_OS_ERROR'), $_POST["amount"], $nochex->displayName, $responseMessage, ["transaction_id" => $_POST["transaction_id"]], (int)$_REQUEST["cIY"], false, $_POST["custom"]);
        $responseAuthorisedMessage= "APC Response.... Order ID: " . $_POST["order_id"] . "... PS_OS_Payment: ". Configuration::get('PS_OS_PAYMENT') . ". Amount: ". $_POST["amount"]. ". Display name: ". $nochex->displayName. ". CurrencyID: ". $_REQUEST["cIY"];
        $nochexDebug->writeDebug($responseAuthorisedMessage);
    }
	
 }
//Tools::redirect('index.php?controller=order-confirmation&id_cart='.Tools::getValue("order_id").'&id_module='.$nochex->id.'&id_order='.$nochex->currentOrder.'&key='.$customer);	
	

