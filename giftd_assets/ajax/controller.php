<?php


require "../../standalone.php";
require '../../classes/components/emarket/class.php';

$instanse = new emarket();
$emarket = cmsController::getInstance()->getModule('emarket');

$giftd = cmsController::getInstance()->getModule('giftd');
$regedit = regedit::getInstance();
$order = $emarket->getBasketOrder();

$client = new Giftd_Client($regedit->getVal("//modules/giftd/user_id"), $regedit->getVal("//modules/giftd/api_key"));
if (!empty($_REQUEST['coupon_code'])) {
    $result = $client->checkByToken($_REQUEST['coupon_code']);
}

if ($result->status == 'ready' || $result->status == 'received') {
    $giftdObject = base64_encode(serialize((array)$result));
    $order->giftd_object = $giftdObject;
    $order->commit();
}