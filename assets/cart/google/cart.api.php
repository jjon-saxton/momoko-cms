<?php

class checkoutCart extends checkout
{
  public function CreateItem($item_name,$item_desc,$quantity,$unit_price,$digital=false,$digital_desc="",$tt_selector="",$private_data="")
  {
    global $dom_items_obj;

    $error_function_name = "CreateItem()";
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"item_name",$item_name);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"item_description",$item_desc);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"quantity",$quantity);
    $this->CheckForError($GLOBALS['mp_type'], $error_function_name,"unit_price",$unit_price);
    $this->CheckForError($GLOBALS['mp_type'], $error_function_name,"currency",$this->currency);

    //escape HTML entities
    $item_name=htmlentities($item_name);
    $item_desc=htmlentities($item_desc);

    //begin the XML doc if this is the first item created
    if(!$dom_items_obj)
    {
      $dom_items_obj=new domDocument("1.0");
      $dom_items_obj->appendChild($dom_items_obj->createElement("items"));
    }

    $dom_item_obj=new domDocument("1.0");

    //Create the <item> tag for the item
    $dom_item=$dom_item_obj->appendChild($dom_item_obj->createElement("item"));

    //Add to the XML
    $dom_item_name=$dom_item->appendChild($dom_item_obj->createElement("item-name"));
    $dom_item_name->appendChild($dom_item_obj->createTextNode($item_name));
    $dom_item_desc=$dom_item->appendChild($dom_item_obj->createElement("item-description"));
    $dom_item_desc->appendChild($dom_item_obj->createTextNode($item_desc));
    $dom_quantity=$dom_item->appendChild($dom_item_obj->createElement("quantity"));
    $dom_quantity->appendChild($dom_item_obj->createTextNode($quantity));
    $dom_price=$dom_item->appendChild($dom_item_obj->createElement("unit-price"));
    $dom_price->setAttribute("currency",$this->currency);
    $dom_price->appendChild($dom_item_obj->createTextNode($unit_price));

    // Create elements for digital content if set
    if ($digital)
    {
      $dom_digital=$dom_item->appendChild($dom_item_obj->createElement('digital-content'));
      $dom_digital_disposition=$dom_digital->appendChild($dom_item_obj->createElement('display-disposition'));
      $dom_digital_disposition->appendChild($dom_item_obj->createTextNode('OPTIMISTIC'));
      if ($digital == 'email')
      {
	$dom_digital_email=$dom_digital->appendChild($dom_item_obj->createElement('email-delivery'));
	$dom_digital_email->appendChild($dom_item_obj->createTextNode('true'));
      }
      elseif ($digital == 'nokey')
      {
	$dom_digital_desc=$dom_digital->appendChild($dom_item_obj->createElement('description'));
	$dom_digital_desc->appendChild($dom_item_obj->createTextNode($digital_desc));
      }
      elseif ($digital == 'urlkey')
      {
	$dom_digital_desc=$dom_digital->appendChild($dom_item_obj->createElement('description'));
	list($url,$key,$desc)=explode('|',$digital_desc);
	if (!@$desc)
	{
		$desc="Please return to our website to continue your order";
	}
	$dom_digital_desc->appendChild($dom_item_obj->createTextNode($desc));
	$dom_digital_key=$dom_digital->appendChild($dom_item_obj->createElement('key'));
	$dom_digital_key->appendChild($dom_item_obj->createTextNode($key));
	$dom_digital_url=$dom_digital->appendChild($dom_item_obj->createElement('url'));
	$dom_digital_url->appendChild($dom_item_obj->createTextNode($url));
      }
    }

    //Create elements if an alternate tax table is associated with this item
    if ($tt_selector)
    {
      $dom_tt_selector=$dom_item->appendChild($dom_item_obj->createElement("tax-table-selector"));
      $dom_tt_selector->appendChild($dom_item_obj->createTextNode($tt_selector));
    }

    //Add private data if needed
    if ($private_data)
    {
      $dom_private_item_data=new domDocument();
      $dom_private_item_data->loadXML($private_data);
      $dom_new_private_item_data=$dom_item->appendChild($dom_item_obj->createElement("merchant-private-item-data"));
      $dom_new_private_item_data_root=$dom_private_item_data->documentElement;
      $dom_new_private_item_data->appendChild($dom_new_private_item_data_root);
    }

    // add the new item
    $dom_items_root=$dom_items_obj->documentElement;
    $dom_item_root=$dom_item_obj->documentElement;
    $dom_item_obj->appendChild($dom_item_root);

    return $dom_item_obj;
  }

  public function CreateCheckoutShoppingCart(array $items)
  {
    $xml_cart=new domDocument("1.0","UTF-8");
    $xml_checkout_cart=$xml_cart->appendChild($xml_cart->createElement('checkout-shopping-cart'));
    $xmlns=$xml_cart->createAttribute('xmlns');
    $xmlns->value=$this->schema_url;
    $xml_checkout_cart->appendChild($xmlns);
    $xml_shopping_cart=$xml_checkout_cart->appendChild($xml_cart->createElement('shopping-cart'));
    $xml_items=$xml_shopping_cart->appendChild($xml_cart->createElement('items'));

    foreach ($items as $item)
    {
      $xml_cart=$this->joinXML($xml_cart->saveXML(),$item->saveXML(),'items');
    }
    $xml_last=new domDocument("1.0");
    $xml_flow_support=$xml_last->appendChild($xml_last->createElement('checkout-flow-support'));
    $xml_merchant_support=$xml_flow_support->appendChild($xml_last->createElement('merchant-checkout-flow-support'));
    $xml_cart=$this->joinXML($xml_cart->saveXML(),$xml_last->saveXML(),'checkout-shopping-cart');

    return $xml_cart->saveXML();
  }
}
