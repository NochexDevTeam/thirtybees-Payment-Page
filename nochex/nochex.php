<?php
/*
Plugin Name: Nochex Payment Gateway for Thirty bees
Description: Accept Nochex Payments, orders are updated using APC.
Version: 2.2
License: GPL2
*/

class nochex extends PaymentModule
{	

	private $_html = '';
	private $_postErrors = array();

	public  $details;
	public  $owner;
	public	$address;
	
	public function __construct()
	{
		
		$this->name = 'nochex';
		$this->tab = 'payments_gateways';
		$this->author = 'Nochex';
		$this->version = 2.2;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';		
		$this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
		
		$this->controllers = ['callback_validation'];
		
		/*--- This array gets all of the configuration information from the Configuration file/table in the database. ---*/
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));
		if (isset($config['NOCHEX_APC_EMAIL']))
			$this->email = $config['NOCHEX_APC_EMAIL'];
		if (isset($config['NOCHEX_APC_TESTMODE']))
			$this->test_mode = $config['NOCHEX_APC_TESTMODE'];
		if (isset($config['NOCHEX_APC_HIDEDETAILS']))
			$this->hide_details = $config['NOCHEX_APC_HIDEDETAILS'];
		if (isset($config['NOCHEX_APC_DEBUG']))
			$this->nochex_debug = $config['NOCHEX_APC_DEBUG'];
		if (isset($config['NOCHEX_APC_XMLCOLLECTION']))
			$this->nochex_xmlcollection = $config['NOCHEX_APC_XMLCOLLECTION'];
		if (isset($config['NOCHEX_APC_POSTAGE']))
			$this->nochex_postage = $config['NOCHEX_APC_POSTAGE'];	 
	
		parent::__construct(); /* The parent construct is required for translations */

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Nochex APC Module');
		$this->description = $this->l('Accept payments by Nochex');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
		if (!isset($this->email))
			$this->warning = $this->l('Account APC Id and Email must be configured in order to use this module correctly');
	}

	public function install()
	{
		if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}
	
	/*--- This function removes the module, and configuration information. ---*/
	public function uninstall()
	{
		if (!Configuration::deleteByName('NOCHEX_APC_EMAIL')
				OR !Configuration::deleteByName('NOCHEX_APC_TESTMODE')
				OR !Configuration::deleteByName('NOCHEX_APC_HIDEDETAILS')
				OR !Configuration::deleteByName('NOCHEX_APC_DEBUG')
				OR !Configuration::deleteByName('NOCHEX_APC_XMLCOLLECTION')
				OR !Configuration::deleteByName('NOCHEX_APC_POSTAGE')		 		
				OR !parent::uninstall())
			return false;
		return true;
	}

    public function writeDebug($DebugData)
    {
        $nochex_debug = Configuration::get('NOCHEX_APC_DEBUG');
        if ($nochex_debug == "checked") {
            $debug_TimeDate = date("m/d/Y h:i:s a", time());
            $stringData = "\n Time and Date: " . $debug_TimeDate . "... " . $DebugData ."... ";
            $debugging = "../modules/nochex/nochex_debug.txt";
            $f = fopen($debugging, 'a') or die("File can't open");
            $ret = fwrite($f, $stringData);
            if ($ret === false) {
                die("Fwrite failed");
            }
            fclose($f)or die("File not close");
        }
    }

	private function _postValidation()
	{
		if (isset($_POST['btnSubmit']))
		{
			if (empty($_POST['email']))
				$this->_postErrors[] = $this->l('Account Email Id is required.');
		}
	}
/*--- Once the update settings button has been pressed on the admin/config file, information is posted and updates the database/configuration details. ---*/
	private function _postProcess()
	{	
	// Funtion and variable which writes to nochex_debug.txt

		if (isset($_POST['btnSubmit']))
		{
			Configuration::updateValue('NOCHEX_APC_EMAIL', $_POST['email']);
			Configuration::updateValue('NOCHEX_APC_TESTMODE', $_POST['test_mode']); /* value is checked or null, stores the state of the checkbox */
			Configuration::updateValue('NOCHEX_APC_HIDEDETAILS', $_POST['hide_details']); /* value is checked or null, stores the state of the checkbox */
			Configuration::updateValue('NOCHEX_APC_DEBUG', $_POST['nochex_debug']); /* value is checked or null, stores the state of the checkbox */
			Configuration::updateValue('NOCHEX_APC_XMLCOLLECTION', $_POST['nochex_xmlcollection']); /* value is checked or null, stores the state of the checkbox */
			Configuration::updateValue('NOCHEX_APC_POSTAGE', $_POST['nochex_postage']); /* value is checked or null, stores the state of the checkbox */		 	
			// Refreshes the page to show updated controls.
			header('Location: ' . $_SERVER['PHP_SELF'] . '?controller=AdminModules&token='.Tools::getValue('token').$identifier.'&configure=nochex&tab_module='.$this->l('Payments & Gateways').'&module_name=nochex');
		}
		$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('Settings updated').'</div>';
	}
	private function _displayNoChex()
	{
		$this->_html .= '<img src="https://www.nochex.com/logobase-secure-images/logobase-banners/clear-mp.png" height="100px" style="float:left; margin-right:15px;"><br style="clear:both;"/><br style="clear:both;"/><b>'.$this->l('This module allows you to accept payments by Nochex (APC Method).').'</b><br /><br />
		'.$this->l('If the client chooses this payment mode, the order will change its status once a positive confirmation is recieved from nochex server').'<br />
		<br /><br />';
	}

	/*---  Function returns the value to the form, which shows the state of the checkbox ---*/
	private function _validateTestCheckbox()
	{
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));
		$this->test_mode = $config['NOCHEX_APC_TESTMODE'];
		return $this->test_mode;
	}
	
	private function _validateBillCheckbox()
	{
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));
		$this->hide_details = $config['NOCHEX_APC_HIDEDETAILS'];
		return $this->hide_details;
	}
	
	private function _validateDebugCheckbox()
	{
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));	
		$this->nochex_debug = $config['NOCHEX_APC_DEBUG'];		
		return $this->nochex_debug;
	}
	
	private function _validateXmlcollectionCheckbox()
	{	
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));
		$this->nochex_xmlcollection = $config['NOCHEX_APC_XMLCOLLECTION'];
		return $this->nochex_xmlcollection;
	}
	
	private function _validatePostageCheckbox()
	{
		$config = Configuration::getMultiple(array('NOCHEX_APC_EMAIL','NOCHEX_APC_TESTMODE','NOCHEX_APC_HIDEDETAILS','NOCHEX_APC_DEBUG','NOCHEX_APC_XMLCOLLECTION','NOCHEX_APC_POSTAGE'));
		$this->nochex_postage = $config['NOCHEX_APC_POSTAGE'];
		return $this->nochex_postage;
	}	
	
	private function _displayForm()
	{
	/*--- Calls the function to return the value of the checkbox ---*/
	$validateTestCheck = $this->_validateTestCheckbox();
	$validateBillCheck = $this->_validateBillCheckbox();
	$validateDebugCheck = $this->_validateDebugCheckbox();
	$validateXmlcollectionCheck = $this->_validateXmlcollectionCheckbox();
	$validatePostageCheck = $this->_validatePostageCheckbox();		
	
	/*--- Form parts that are added in the Configuration file of the nochex module. ---*/
		$this->_html .=
		'<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Account details').'</legend>
				<table border="0" width="1250" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">'.$this->l('Please specify your Nochex account details').'.<br /><br /></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Nochex Merchant ID / Email Address').'</td><td><input type="text" name="email" value="'.htmlentities(Tools::getValue('email', $this->email), ENT_COMPAT, 'UTF-8').'" style="width: 250px;" /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;"> Nochex Merchant ID / Email Address, This is your Nochex Merchant ID, e.g. test@test.com or one that has been created: e.g. test</p></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Test Mode').'</td><td><input type="checkbox" name="test_mode" value="checked" '. $validateTestCheck .' /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;"> Test Mode, If the Test mode option has been selected, the system will be in test mode. Note (leave unchecked for Live transactions.) </p></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Hide Billing Details').'</td><td><input type="checkbox" name="hide_details" value="checked" '. $validateBillCheck .' /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;"> Hide Billing Details, If the Hide Billing Details option has been checked then billing details will be hidden, Leave unchecked if you want customers to see billing details.</p></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Debug').'</td><td><input type="checkbox" name="nochex_debug" value="checked" '. $validateDebugCheck .' /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;"> Debug, If the Debug option has been selected, details of the module will be saved to a file. nochex_debug.txt which can be found in the nochex module which can be found somewhere like: www.test.com/prestashop/modules/nochex/nochex_debug.txt, leave unchecked if you dont want to record data about the system.</p></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Detailed Product Information').'</td><td><input type="checkbox" name="nochex_xmlcollection" value="checked" '. $validateXmlcollectionCheck .' /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;">Detailed Product Information: Display order details in a table structured format on the Nochex Payment Page</p></td></tr>
					<tr><td width="300" style="height: 35px;">'.$this->l('Postage').'</td><td><input type="checkbox" name="nochex_postage" value="checked" '. $validatePostageCheck .' /></td><td width="950"><p style="font-style:italic; text-size:7px; padding-left:10px;">Postage: Display the Postage amount separate from the total amount on the Nochex Payment Page</p></td></tr>					
					<tr><td></td><td><input class="button" name="btnSubmit" value="'.$this->l('Update settings').'" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
	}

	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (!empty($_POST))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error">'. $err .'</div>';
		}
		else
			$this->_html .= '<br />';

		$this->_displayNoChex();
		$this->_displayForm();

		return $this->_html;
	}

	public function hookDisplayPayment($params)
	{
		global $smarty,$cart, $currency;
		
		/* Get Shop Default Currency */
		$defaultCurrency = Configuration::get('PS_CURRENCY_DEFAULT');
		
		//Convert Currency to Pounds
		$currency = new Currency((int)$cart->id_currency);
		$currencyGBP = new Currency((int)$defaultCurrency);		
		
		$c_rate = (is_array($currency) ? $currency['conversion_rate'] : $currency->conversion_rate);
		
		// Get Customers Details
		$customer = new Customer(intval($params['cart']->id_customer));
		
		//--- get the customers delivery address
		$del_add = new Address(intval($params['cart']->id_address_delivery));
        $del_add_fields = $del_add->getFields();

		/*--- Gets the configuration details that were saved in the Thirty bees admin area  ---*/
		$apc_email = Configuration::get('NOCHEX_APC_EMAIL');
		$test_mode = Configuration::get('NOCHEX_APC_TESTMODE');
		$hide_details = Configuration::get('NOCHEX_APC_HIDEDETAILS');
		$nochex_debug = Configuration::get('NOCHEX_APC_DEBUG');
		$nochex_xmlcollection = Configuration::get('NOCHEX_APC_XMLCOLLECTION');
		$nochex_postage = Configuration::get('NOCHEX_APC_POSTAGE');		 
		
		/* Show Separate Postage Amount Feature */
		if($nochex_postage == "checked"){
		
		$totalAmount = number_format(Tools::convertPriceFull($cart->getOrderTotal(true, 3),$currency , $currencyGBP), 2, '.', '');
		$totalShipping = number_format(Tools::convertPriceFull($cart->getOrderTotal(true, Cart::ONLY_SHIPPING),$currency , $currencyGBP), 2, '.', '');		
		$totalAmount = number_format($totalAmount - $totalShipping, 2, '.', ''); 
		
		}else{
		
		$totalShipping = "";
		$totalAmount =  number_format(Tools::convertPriceFull($cart->getOrderTotal(true, 3),$currency , $currencyGBP), 2, '.', '');
		
		}
				
		/* XML / Detailed Product Information Feature */		
		if($nochex_xmlcollection == "checked"){
		
		//--- get the product details  
		$productDetails = $cart->getProducts();
		$item_collection = "<items>";
		
		//--- Loops through and stores each product that has been ordered in the $prodDet variable.
		foreach($productDetails as $details_product)
		{
		
		$item_collection .= "<item><id>". $details_product['id_product'] . "</id><name>" . $details_product['name'] . "</name><description>".$details_product['description_short']."</description><quantity>" . $details_product['quantity']  . "</quantity><price>" .  number_format(Tools::convertPriceFull($details_product['total_wt'],$currency , $currencyGBP), 2, '.', '')  . "</price></item>";
		
		}
		$item_collection .= "</items>";
		
		$prodDet = "Order created for: " . intval($params['cart']->id);
		
		}else{
		
		$item_collection = "";
		//--- get the product details  
		$productDetails = $cart->getProducts();
		$prodDet = "";
		
		//--- Loops through and stores each product that has been ordered in the $prodDet variable.
		foreach($productDetails as $details_product)
		{
		
		$prodDet .= "Product ID: ". $details_product['id_product'] . ", Product Name: " . $details_product['name'] . ", Quantity: " . $details_product['quantity']  . ", Amount: &#163 " .  number_format(Tools::convertPriceFull($details_product['total_wt'],$currency , $currencyGBP), 2, '.', '')  . ". ";
		
		}
		
		$prodDet .= " ";
		
		}
		
		// Funtion and variable which writes to nochex_debug.txt
		$submit_Config = 'Configuration Details... NOCHEX_APC_EMAIL: ' . $apc_email . '. NOCHEX_APC_TESTMODE: '. $test_mode .'. NOCHEX_APC_HIDEDETAILS: '.$hide_details.'. NOCHEX_APC_DEBUG: '.$nochex_debug;
				
		
		/* Hide Billing Details Feature */
		/* This feature will hide the billing details on the Nochex payment page*/
		if ($hide_details == "checked"){
		
			$hideBilling = 1;
		
		}else{
		
			$hideBilling = 0;
		
		} 
		
		// get the billing address and details
		$bill_add = new Address(intval($params['cart']->id_address_invoice));
        $bill_add_fields = $bill_add->getFields();
	 		
		//// Funtion and variable which writes to nochex_debug.txt
		$submitOrder_Details = 'Order Details... Merchant_id: ' . $apc_email . '. amount: ' . number_format(round($totalAmount, 2), 2, '.', '') . '. order_id: ' . intval($params['cart']->id); 
		//// Funtion and variable which writes to nochex_debug.txt
		$submitOrder_Contents = 'Order Contents... Description: ' . $prodDet; 
		//// Funtion and variable which writes to nochex_debug.txt
		$submitOrder_Billing = 'Billing Details... ';
		//// Funtion and variable which writes to nochex_debug.txt
		$submitOrder_Delivery = 'Delivery Details... ';
		//// Funtion and variable which writes to nochex_debug.txt
		$submitOrder_Contact = 'Contact Information... customer_phone_number: ' . $bill_add_fields['phone_mobile'] . '. email_address: ' . $customer->email;	
		
		/* Callback Feature - This has been updated - optional 2 wont be picked up / received in apc */
		$enabledCB = "Enabled";		
		// echo ;
		$callback_url = $this->context->link->getModuleLink($this->name, 'callback_validation', [], true) . '?cIY='.(int)$defaultCurrency;		
		 
		/* Sanitise and Validate input before processing */
		$billing_first_name = filter_var($bill_add_fields['firstname'], FILTER_SANITIZE_STRING);
		$billing_last_name = filter_var($bill_add_fields['lastname'], FILTER_SANITIZE_STRING);
		$billing_address = filter_var($bill_add_fields['address1'], FILTER_SANITIZE_STRING);
		$billing_city = filter_var($bill_add_fields['city'], FILTER_SANITIZE_STRING);
		$billing_postcode = $bill_add_fields['postcode'];

		$billing_phone = preg_replace('/[^0-9]/', '', $bill_add_fields['phone_mobile']);
		$billing_email = filter_var($customer->email, FILTER_SANITIZE_EMAIL);

		$shipping_first_name = filter_var($del_add_fields['firstname'], FILTER_SANITIZE_STRING);
		$shipping_last_name = filter_var($del_add_fields['lastname'], FILTER_SANITIZE_STRING);
		$shipping_address = filter_var($del_add_fields['address1'], FILTER_SANITIZE_STRING);
		$shipping_city = filter_var($del_add_fields['city'], FILTER_SANITIZE_STRING);
		$shipping_postcode = $del_add_fields['postcode'];
 
 
		$ZIPREG=array(
		 "US"=>"^\d{5}([\-]?\d{4})?$",
		 "GB"=>"^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
		 "UK"=>"^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
		 "DE"=>"\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b",
		 "CA"=>"^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$",
		 "FR"=>"^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$",
		 "IT"=>"^(V-|I-)?[0-9]{5}$",
		 "AU"=>"^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$",
		 "NL"=>"^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$",
		 "ES"=>"^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$",
		 "DK"=>"^([D-d][K-k])?( |-)?[1-9]{1}[0-9]{3}$",
		 "SE"=>"^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$",
		 "BE"=>"^[1-9]{1}[0-9]{3}$"
		);

		if (!preg_match("/".$ZIPREG[$billing_country]."/i",$billing_postcode)){
		 //Validation failed, provided zip/postal code is not valid.
			$billing_postcode = "";
		 }  

		if (!preg_match("/".$ZIPREG[$delivery_country]."/i",$shipping_postcode)){
		 //Validation failed, provided zip/postal code is not valid.
			$shipping_postcode = "";
		 }  
		/*---  Gets the variables which will be retrieved from the order process form, and the variables will be sent to Nochex.tpl, as the customer gets to the final stage of the order and about to press pay with Nochex.  number_format(round($amo, 2), 2, '.', '')---*/
		 $smarty->assign(array(
			'merchant_id' => $apc_email,
			'amount' => $totalAmount,
			'order_id' => intval($params['cart']->id),
			'description' => $prodDet,
			'postage' => $totalShipping,
			'xml_item_collection' => $item_collection,
            'billing_fullname' => $billing_first_name.', '.$billing_last_name,
			'billing_address' => $billing_address,
			'billing_city' => $billing_city,
			'billing_country' => $billing_country,
			'billing_postcode' => $billing_postcode,
            'delivery_fullname' => $shipping_first_name . ', '. $shipping_last_name,
			'delivery_address' => $shipping_address,
			'delivery_city' => $shipping_city,
			'delivery_country' => $delivery_country,
			'delivery_postcode' => $shipping_postcode,
			'customer_phone_number' => $billing_phone,
			'hide_billing_details' => $hideBilling,
			'optional_1' => $params['cart']->secure_key,
			'email_address' => $billing_email,
			'responderurl' => $callback_url,
			'cancelurl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'order',
			'successurl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/nochex/success.php?id_cart='.$cart->id,
			'optional_2' => $enabledCB,			
		));
		
		/* Test Mode Feature */
		if($test_mode=="checked")
		{
		/*--- If test mode is enabled then pass the below details into the payment form  ---*/
		 $smarty->assign(array(
				'teststatus' => true,
				'test_transaction' => '100',
				'test_success_url' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/nochex/success.php?id_cart='.$cart->id));
				
		// Funtion and variable which writes to nochex_debug.txt
		$test_mode_Info = 'test_status = true'; 
		
		}
		else
		{
		/*--- else test mode variable hasn't been checked then data will be sent to the form in live mode. ---*/
		 $smarty->assign(array('teststatus' => false));
		 
		 // Funtion and variable which writes to nochex_debug.txt
		 $test_mode_Info = 'test_status = false'; 
		}
		
		if($nochex_debug == "checked"){
			Logger::addLog("Nochex Debug Log (Config): {$submit_Config}");
			Logger::addLog("Nochex Debug Log (Details): {$submitOrder_Details}");
			Logger::addLog("Nochex Debug Log (Order): {$submitOrder_Contents}");
			Logger::addLog("Nochex Debug Log (Billing): {$submitOrder_Billing}");
			Logger::addLog("Nochex Debug Log (Delivery): {$submitOrder_Delivery}");
			Logger::addLog("Nochex Debug Log (Contact): {$submitOrder_Contact}");
			Logger::addLog("Nochex Debug Log (Test): {$test_mode_Info}");
		}
		
		return $this->display(__FILE__, 'nochex.tpl');
	}

}

?>