<?php
/**
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = basename(__FILE__, '.php');

$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$status = $_POST["status"];
$invoiceId = $_POST["platform_order_id"];
$transactionId = $_POST["payment_id"];
$installment = $_POST["installment"];
$signature = $_POST["signature"];

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
checkCbTransID($transactionId);

$data=$_POST["random_nr"].$_POST['platform_order_id'];

$signature = base64_decode($signature);
$expected = hash_hmac('SHA256', $data, $gatewayParams['shopier_api_secret'], true);
if ($signature == $expected) {
	$status = strtolower($status);
	if ($status == "success") {
		addInvoicePayment(
			$invoiceId,
			$transactionId,
			$paymentAmount,
			$paymentFee,
			$gatewayModuleName
		);
	}
}
logTransaction($gatewayParams['name'], $_POST, $status);
$systemUrl = $gatewayParams['systemurl'];
$redirectUrl = $systemUrl.'/viewinvoice.php?id='.$invoiceId;
header('Location:'.$redirectUrl);