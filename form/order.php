<?php
require_once $GLOBALS['CFG']->basedir.'/assets/php/dal/load.inc.php';
define ('GMID','158991102835068');

class MomokoLITECustom implements MomokoLITEObject
{
 public $args=array();
 public $info=array();
 private $packages=array();

 public function __construct($path=null)
 {
  if (is_array($path))
  {
   $this->args=$path;
  }
	$packages=new DataBaseTable('ss_packages','saxton');
	$query=$packages->getData(array('name','product','start_price','renewal_price','renewal_duration'));
	$this->packages=$query->toArray();
	unset($query,$packages);
  $this->info=$this->readInfo();
 }
 
 public function __get($var)
 {
  if (array_key_exists($var,$this->info))
  {
   return $this->info[$var];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  $this->info[$key]=$value;
 }

 public function get()
 {
  return $this->inner_html;
 }

 public function put()
 {
	 $orders=new DataBaseTable('ss_orders','saxton');
		foreach ($this->packages as $package) //find the right package!
		{
			if ($package['name'] == $_POST['package'])
			{
				$data=$package; //store it in array to add to database
			}
		}
  if ((preg_match("/^[-a-z0-9]+\.[a-z][a-z]|biz|cat|com|edu|gov|int|mil|net|org|pro|tel|aero|arpa|asia|coop|info|jobs|mobi|name|museum|travel$/",$_POST['first']) < 1 || preg_match("/^[-a-z0-9]+\.[a-z][a-z]|biz|cat|com|edu|gov|int|mil|net|org|pro|tel|aero|arpa|asia|coop|info|jobs|mobi|name|museum|travel$/",$_POST['last']) < 1)) // Ensures First and Last name are not URLS! This should block most spammers
  {
   $data['name']=$_POST['first'].' '.$_POST['last'];
  }
  else
  {
   $data['name']='spammer!';
  }
		$data['email']=$_POST['email'];
		$data['phone']=$_POST['phone'];
		
		if (@$data['name'] != 'spammer!' && $order_num=$orders->putData($data))
		{   
	 $items[0]['name']=$data['product'];
   $items[0]['description']=$data['product']." order for ".$data['name']." #".$order_num;
   $items[0]['price']=$data['start_price'];
   $items[0]['digital']='nokey';
   $items[0]['digital_desc']="Thank you for your order. We will contact your shortly via the phone or e-mail you provided. Your card will not be charged until we confirm your order!";
   $items[0]['quantity']=1;
   $items[0]['tax_table']="";
   //$items[0]['merchant_private_data']="<record-number>".$registrant."</record-number>";
   $items[0]['merchant_private_data']="";
			
			//Open cart and send it
			require_once $GLOBALS['CFG']->basedir.'/assets/cart/'.$_POST['vpos'].'/shell.inc.php';
			echo openCart(GMID,$items);
		}
		$info['title']='Order Failed!';
		$info['inner_body']=<<<HTML
<h2>Order Failed!</h2>
<p>An unknown communications error occured between our site and the virtual point of sales resulting in your order being dropped. Please try again.
HTML;
  $info['full_html']=<<<HTML
<html>
<head>
<title>{$info['title']}</title>
</head>
<body>
{$info['inner_body']}
</body>
</html>
HTML;
  return $info;
 }

 public function readInfo()
 {
  $order_options="<select id=\"item\" name=\"package\">\n";
  if (@$_POST['package'])
  {
   return $this->put();
  }
  foreach ($this->packages as $package)
  {
   if ($this->args['package'] == $package['name'])
   {
    $order_options.="<option selected=selected value=\"{$package['name']}\">{$package['product']}</option>\n";
   }
   else
   {
    $order_options.="<option value=\"{$package['name']}\">{$package['product']}</option>\n";
   }
  }
  $order_options.="</select>";
  $info['title']="Order Form";
  $info['inner_body']=<<<HTML
<h2>Order Form</h2>
<p>Please fill out the form below to begin your ordering process. All information is required, except we only need either an e-mail or phone to contact you. E-mail is preferred.
<form action="{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/index.php/form/order.php?package={$this->args['package']}" method=post><input type=hidden name="vpos" value="google">
<ul class="nobullet noindent">
<!--<li><label for="vpos">Pay By:</label>: {$cart_options}</li>-->
<li><label for="item">Package:</label> {$order_options}</li>
<li><label for="fname">First Name:</label> <input type=text required=required id="fname" name="first"></li>
<li><label for="lname">Last Name:</label> <input type=text required=required id="lname" name="last"></li>
<li><label for="email">E-Mail Address:</label> <input type=email id="email" name="email"></li>
<li><label for="phone">Phone Number:</label> <input type=tel id="phone" name="phone"></li>
</ul>
<div id="google"><input type="image" name="Google Checkout" alt="Fast checkout through Google"
        src="http://checkout.google.com/buttons/checkout.gif?merchant_id={GMID}&w=180&h=46&style=white&variant=text&loc=en_US" height="46" width="180"></div>
<!--<div id="paypal">
</div>-->
</form>
HTML;
  $info['full_html']=<<<HTML
<html>
<head>
<title>{$info['title']}</title>
</head>
<body>
{$info['inner_body']}
</body>
</html>
HTML;

  return $info;
 }
}
