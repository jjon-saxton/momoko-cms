<?php
//include the API
include ($GLOBALS['CFG']->basedir."/assets/cart/google/global.api.php");
include ($GLOBALS['CFG']->basedir."/assets/cart/google/cart.api.php");

function openCart($id,array $items)
{
  $cart=new checkoutCart($id,'USD');

  foreach ($items as $item)
  {
    $xml_items[]=$cart->CreateItem($item['name'],$item['description'],$item['quantity'],$item['price'],$item['digital'],$item['digital_desc'],$item['tax_table'],$item['merchant_private_data']);
  }

  $xml_cart=$cart->CreateCheckoutShoppingCart($xml_items);
  $transmit_response=$cart->SendRequest($xml_cart);
  $cart->processResponse($transmit_response);
}