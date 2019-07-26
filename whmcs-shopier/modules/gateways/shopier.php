<?php
/**
 * WHMCS Shopier Payment Gateway Module
 *
 * Shopier Payment Gateway Module allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
 
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * @return array
 */
function shopier_MetaData()
{
    return array(
        'DisplayName' => 'Pay with Credit Card',
        'APIVersion' => '1.4',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * @return array
 */
function shopier_config()
{
	$responseUrl = '';
	$moduleName = 'shopier';
	
	$result = Capsule::table('tblpaymentgateways')
				->where('gateway', $moduleName)
				->get();
			
	if ($result) {
		$params = getGatewayVariables($moduleName);
		$systemUrl = $params['systemurl'];
		$systemUrl .= '/modules/gateways/callback/' . $moduleName . '.php';
		$responseUrl = 'I certify that I have provided Shopier with the proper Response URL:';
		$responseUrl .= ' <strong>'.$systemUrl.'</strong></span>';
	}
	
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Pay with Credit Card',
        ),
        'shopier_api_key' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'This can be obtained from Shopier Panel',
        ),
        'shopier_api_secret' => array(
            'FriendlyName' => 'Secret',
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'This can be obtained from Shopier Panel',
        ),
        'shopier_payment_url' => array(
            'FriendlyName' => 'Payment Endpoint URL',
            'Type' => 'text',
            'Size' => '25',
			'Default' => 'https://www.shopier.com/ShowProduct/api_pay4.php',
        ),
         'shopier_cancel_url' => array(
            'FriendlyName' => 'Cancel Endpoint URL',
            'Type' => 'text',
            'Size' => '25',
			'Default' => 'https://www.shopier.com/pg_sandbox/pg_cancel.php',
        ),
		'shopier_website_index' => array(
            'FriendlyName' => 'Website İndex',
            'Type' => 'text',
            'Size' => '25',
			'Default' => '1',
        ),
        'shopier_response_url' => array(
            'FriendlyName' => '',
            'Type' => 'yesno',
            'Description' => $responseUrl
        ),
    );
}

/**
 * Payment link.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return string
 */
function shopier_link($params)
{
	$address = $params['clientdetails']['address1'];
	if (!empty($params['clientdetails']['address2'])) {
		$billingAddress .= ' '.$params['clientdetails']['address2'];
	}
	if (!empty($params['clientdetails']['state'])) {
		$billingAddress .= ' '.$params['clientdetails']['state'];
	}

	$result = Capsule::table('tblclients')
				->where('id', $params['clientdetails']['id'])
				->get();
			
	foreach ($result as $client) {
    	$user_registered = $client->datecreated;
	}
	$time_elapsed = time() - strtotime($user_registered);
	$buyer_account_age = (int)($time_elapsed/86400);
		
	$productinfo = str_replace('"', '', $params["description"]);
    $productinfo = str_replace('&quot;', '', $productinfo);
	
	if ($params['currency']=="USD"){
		$currency=1;
	}else if ($params['currency']=="TRY"){
	$currency=0;
	}else if ($params['currency']=="EUR"){
		$currency=2;
	}else {
		$currency=0;
	}
	$current_language=$_SESSION['Language'];
	$current_lang=1;
	if ($current_language == "turkish"){
	$current_lang=0;}
	$modul_version='1.4';
	$version=$raw->version;
	srand(time(NULL));
	$random_nr=rand(100000,999999);
	$postfields = array(
		'API_key' => $params['shopier_api_key'],
		'website_index' => $params['shopier_website_index'],
		'platform_order_id' => $params['invoiceid'],
		'product_name' => $productinfo,
		'product_type' => 1,
		'buyer_name' => $params['clientdetails']['firstname'],
		'buyer_surname' => $params['clientdetails']['lastname'],
		'buyer_email' => $params['clientdetails']['email'],
		'buyer_account_age' => $buyer_account_age,
		'buyer_id_nr' => $params['clientdetails']['id'],
		'buyer_phone' => $params['clientdetails']['phonenumber'],
		'billing_address' => $billingAddress,
		'billing_city' => $params['clientdetails']['city'],
		'billing_country' => $params['clientdetails']['country'],
		'billing_postcode' => $params['clientdetails']['postcode'],
		'shipping_address' => 'NA',
		'shipping_city' => 'NA',
		'shipping_country' => 'NA',
		'shipping_postcode' => 'NA',
		'total_order_value' => $params['amount'],
		'currency' => $currency,
		'current_language'=>0,
		'modul_version' =>$modul_version,
		'version' =>$version,
		'platform' => 4,
		'is_in_frame' => 0,
		'random_nr' => $random_nr
	);
	
	//$data = implode('', $postfields);
	$data=$postfields["random_nr"].$postfields['platform_order_id'].$postfields['total_order_value'].$postfields['currency'];
	$signature = hash_hmac('SHA256', $data, $params['shopier_api_secret'], true);
	$signature = base64_encode($signature);
	$postfields['signature'] = $signature;

    $langPayNow = $params['langpaynow'];
    $url = $params['shopier_payment_url'];

    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}
