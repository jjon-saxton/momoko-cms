<?php
$GLOBALS['logfile']=$GLOBALS['CFG']->basedir."/error/logs/checkout.log";
$GLOBALS['error_report_type']=2;
$GLOBALS['error_logfile']=$GLOBALS['CFG']->basedir."/error/logs/checkout_errors.log";

set_error_handler("error_handler");

$GLOBALS['mp_type']="MISSING_PARAM"; 

class checkout
{
  protected $currency;
  protected $schema_url;
  protected $merchant_id;
  protected $merchant_key;
  protected $checkout_url;
  protected $checkout_diagnose_url;
  protected $request_url;
  protected $request_diagnose_url;

  public function __construct($merchant_id,$currency)
  {
    $this->currency=$currency;
    $this->schema_url="http://checkout.google.com/schema/2";
    $this->merchant_id=$merchant_id;
    $this->merchant_key=$this->getMerchantKey($merchant_id);

    $base_url="https://checkout.google.com/cws/v2/Merchant/".$this->merchant_id; //Production
				//$base_url="https://sandbox.google.com/checkout/cws/v2/Merchant/".$this->merchant_id; //Testing
    $this->checkout_url=$base_url."/checkout";
    $this->checkout_diagnose_url=$base_url."/checkout/diagnose";
    $this->request_url=$base_url."/request";
    $this->request_diagnose_url=$base_url."/request/diagnose";
  }

  private function GetMerchantKey($mid)
  {
   $merchants=new DataBaseTable('ss_merchants','saxton');
			$merchant=$merchants->getData('key','id='.$mid,null,1);
			$merchant=$merchant->first();
			
			return $merchant->key;
  }

  protected function CalcHmacSha1($data)
  {
    $key=$GLOBALS['merchant_key'];

    // check for errors
    $error_function_name="CalcHmacSha1()";

    // the $data and $key vars must be popluated, not NULL
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"data",$data);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"this->merchant_key",$key);

    $blocksize=64;
    $hashfunc='sha1';

    if (strlen($key) > $blocksize)
    {
      $key=pack('H*',$hashfunc($key));
    }

    $key=str_pad($key,$blocksize,chr(0x00));
    $ipad=str_repeat(chr(0x36),$blocksize);
    $opad=str_repeat(chr(0x5c),$blocksize);
    $hmac= pack('H*',$hashfunc(($key^$opad).pack('H*',$hashfunc($key^$ipad).$data)));

    return $hmac;
  }

  public function SendRequest($request)
  {
    $post_url=$this->request_url;
    //Error checker
    $error_function_name="SendRequest();";

    // Check for missing parameters
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"request", $request);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"request_url", $this->request_url);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"merchant_id",$this->merchant_id);
    $this->CheckForError($GLOBALS['mp_type'],$error_function_name,"merchant_key",$this->merchant_key);

    // Log outgoing message
    $this->LogMessage($GLOBALS['logfile'],$request);

    // Execute
    $response=$this->GetCurlResponse($request);

    // Log incoming message
    $this->LogMessage($GLOBALS['logfile'],$response);

    return $response;
  }

  protected function GetCurlResponse($request)
  {
    $post_url=$this->request_url;
    $rq=curl_init();
    curl_setopt($rq,CURLOPT_URL,$post_url);
    curl_setopt($rq,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($rq,CURLOPT_SSL_VERIFYPEER,true);
    curl_setopt($rq,CURLOPT_SSL_VERIFYHOST,2);

    $pos=strpos($post_url,"request");
    if ($pos == TRUE)
    {
      // Set HTTP Basic auth
      curl_setopt($rq,CURLOPT_USERPWD,$this->merchant_id.":".$this->merchant_key);

      //Set HTTP headers
      $header[]="Content-type: application/xml";
      $header[]="Accept: application/xml";
      curl_setopt($rq,CURLOPT_HTTPHEADER,$header);
    }

    curl_setopt($rq,CURLOPT_POST,1);
    curl_setopt($rq,CURLOPT_POSTFIELDS,$request);

    // Execute
    $response=curl_exec($rq);

    // Verify execution
    if (curl_errno($rq))
    {
      trigger_error(curl_error($rq),E_USER_ERROR);
    }
    else
    {
      curl_close($rq);
    }

    return $response;
  }

  public function processResponse($xml)
  {
    $dom_response=new DOMDocument();
    $dom_response->loadXML($xml);
    $root_element=$dom_response->documentElement;
    $root_tag=$root_element->nodeName;

    if ($root_tag == 'checkout-redirect')
    {
      $redirect=$root_element->getElementsByTagname("redirect-url");
      if (!(empty($redirect)))
      {
        header ("Location: ".$redirect->item(0)->nodeValue);
								exit();
      }
    }
    //else TODO: Add else statement to use DisplayDiagnoseResponse() on error
  }

  public function DisplayDiagnoseResponse($request,$post_url,$xml,$action)
  {
    $diagnose_response=SendRequest($request,$post_url);

    $validated=false;
    $dom_response=new DOMDocument();
    $dom_response->loadXML($diagnose_response);
    $root_element=$dom_response->documentElement;
    $root_tag=$root_element->nodeName;

    if ($root_tag == "diagnosis")
    {
      $error_message=$root_element->getElementsByTagname("warnings");
      if (!(empty($error_message)))
      {
	$string=$error_message[0]->getElementsByTagname("string");
	$result=$string;
      }
      else
      {
	$validated=true;
      }
    }
    elseif ($root_tag == "error")
    {
      $error_message=$root_element->getElementsByTagname("error-message");
      $result=$error_message;
      $warning_messages=$root_element->getElementsByTagname("string");
    }
    elseif ($root_tag == "request-received")
    {
      $validated=true;
    }

  
    /*if ($validated == false && ($GLOBALS["error_report_type"] == "2" || 
        $GLOBALS["error_report_type"] == "3"))
    {
        echo "<tr><td style=\"color:red\"><p>" .
            "<span style=\"text-align:center\">" .
            "<h2>This XML is NOT Validated!</h2></span></p>";
        foreach($result as $message) {
            echo "<p style=\"text-align:left\"><b>" . 
            htmlentities($message->get_content()) . "</b></p>";
        }
        if (($root_tag == "error") && (sizeof($warning_messages)) > 0) {
            foreach ($warning_messages as $message) {
                echo "<p style=\"text-align:left\"><b>" . 
                htmlentities($message->get_content()) . "</b></p>";
            }
        }
        if ($action == "debug") {
            echo "<p><form method=POST action=DebuggingTool.php>";
            echo "<input type=\"hidden\" name=\"xml\" value=\"" . 
                htmlentities($xml) . "\"/>";
            echo "<input type=\"hidden\" name=\"openFile\" value=\"false\"/>";
            echo "<input type=\"hidden\" name=\"toolType\" " .
                "value=\"Validate XML\"/>";
            echo "<input type=\"submit\" name=\"Debug\" value=\"Debug XML\"/>";
            echo "</form></p></td></tr>";
        }
  }*/

    return $validated;
  }

  protected function CheckForError($error_type,$function_name,$param_name,$param_value)
  {
    if ($param_value == "")
    {
      //trigger_error(error_msg($error_type,$function_name,$param_name,$param_value),E_USER_ERROR);
    }
  }

  protected function LogMessage($log,$message)
  {
    if(!$log_file=fopen($log,"a"))
    {
      $errstr="Cannot open '{$log}' file";
      trigger_error($errstr,E_USER_ERROR);
    }
    fwrite($log_file,sprintf("\r\n\r\n%s",date("r",time())));
    fwrite($log_file,sprintf("\r\n%s",$message));
    fclose($log_file);
  }

  protected function joinXML($parent,$child,$tag=null)
  {
    $DOMChild=new DOMDocument;
    $DOMChild->loadXML($child);
    $node=$DOMChild->documentElement;

    $DOMParent=new DOMDocument;
    $DOMParent->loadXML($parent);

    $node=$DOMParent->importNode($node,true);

    if ($tag !== NULL)
    {
      $tag=$DOMParent->getElementsByTagname($tag)->item(0);
      $tag->appendChild($node);
    }
    else
    {
      $DOMParent->documentElement->appendChild($node);
    }

    return $DOMParent;
  }
}

function error_msg($error_type, $function_name, $param_name="", 
    $param_value="") {

    /*
     * This code block selects the error message that corresponds to
     * the value of the $error_type variable.
     *
     * +++ CHANGE ME +++
     * You can change any of the error messages logged for these errors.
     */

    switch ($error_type) {

        /*
         * MISSING_PARAM error
         * A function call omits a required parameter.
         */
        case "MISSING_PARAM":
            $errstr = "Error calling function \"" . $function_name . 
                "\": Missing Parameter: \"$" . $param_name . 
                "\" must be provided.";
            break;

        /*
         * INVALID_INPUT_ARRAY error
         * AddAreas() function called with invalid value for
         * $state_areas or $zip_areas parameter
         */
        case "INVALID_INPUT_ARRAY":
            $errstr = "Error calling function \"" . $function_name . 
                "\": Invalid Input: \"" . $param_name . 
                "\" should be an array.";
            break;

        /*
         * MISSING_CURRENCY error
         * The $GLOBALS["currency"] value is empty.
         */
        case "MISSING_CURRENCY":
            $errstr = "Error calling function \"" . $function_name . 
                "\": Missing Parameter: \"\$GLOBALS[\"currency\"]\"" . 
                "must be set when the \"\$amount\" is set.";
            break;

        /*
         * INVALID_ALLOW_OR_EXCLUDE_VALUE error
         * AddAreas() function called with invalid value for 
         * $allow_or_exclude parameter.
         */
        case "INVALID_ALLOW_OR_EXCLUDE_VALUE";
            $errstr = "Error calling function \"" . $function_name . 
                "\": Areas must either be allowed or excluded.";
            break;

        /*
         * MISSING_TRACKING error
         * The ChangeShippingInfo() function in 
         * OrderProcessingAPIFunctions.php is being called without 
         * specifying a tracking number even though a shipping 
         * carrier is specified.
         */
        case "MISSING_TRACKING":
            $errstr = "Error calling function \"" . $function_name . 
                "\": Missing Parameter: \"\$tracking_number\" must be set " .
                "if the \"\$carrier\" is set.";
            break;

        default:
            break;
    }

    return $errstr;
}

function error_handler($errno,$errstr,$errfile,$errline)
{
  $time=date("r",time());

  $errstr_echo=$time."<br>";
  $errstr_echo.="Fetal error in line $errline of $errfile <br>";
  $errstr_echo.="-{$errstr}<br><br>";

  $errstr_log=$time."\r\n";
  $errstr_log="Fetal error in line {$errline} of {$errfile}\r\n";
  $errstr_log="-{$errstr}\r\n\r\n";

  if ($GLOBALS['error_report_type'] == 2 || $GLOBALS['error_report_type']=3)
  {
    echo $errstr_echo;
  }

  if ($GLOBALS['error_report_type'] == 1 || $GLOBALS['error_report_type']=3)
  {
    error_log($errstr_log,3,$GLOBALS['error_logfile']);
  }

  die();
}
