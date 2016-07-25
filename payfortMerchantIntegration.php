<?php


class payfortMerchantIntegration
{

    private $amount;                //Amount
    private $currency;              //Currency
    private $merchant_identifier;   //payfort merchant identifier
    private $access_code;           //merchant access code
    private $sha_phrase;            //sha phrase
    private $order_description;     //order description
    private $merchant_reference;    //merchant order reference
    private $customer_email;        //Customer Email
    private $customer_ip;           //ip address
    private $language;              //language
    private $command;               //operation commnad (AUTHORIZATION/PURCHASE/TOKENIZATION)
    private $return_url;            //back to merchant url
    private $testMode;              //mode[test/production]
    private $requestURL;            //request url
    private $algorithm;             //sha algorithm
    private $customer_name;
    private $token_name;





    /**
     * request Token and view Payment page
     */
    public function tokenizationRequest()
    {
        $requestParams = array(
            'merchant_identifier'   => $this->getMerchantIdentifier(),
            'access_code'           => $this->getAccessCode(),
            'merchant_reference'    => $this->getMerchantReference(),
            'language'              => $this->getLanguage(),
            'service_command'       => $this->getCommand(),
            'return_url'            => $this->getReturnUrl()
        );


        ksort($requestParams);  //sort parameters
        $requestParams['signature'] = $this->calculateSignature(); //calculate signature

        //build form
        //auto submit form to get credit card page
        echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n<head></head>\n<body>\n";
        echo "<form action='".$this->requestURL."' method='post' name='frm'>\n";

        foreach ($requestParams as $a => $b)
        {
            echo "\t<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."'>\n";
        }
        echo "\t<script type='text/javascript'>\n";
        echo "\t\tdocument.frm.submit();\n";
        echo "\t</script>\n";
        echo "</form>\n</body>\n</html>";
        die();

    }


    /**
     * calculate signature
     * @return string
     */
    public function calculateSignature()
    {
        $shaPhrase = $this->getShaPhrase();
        $shaString = '';

        $requestParams = array(
            'merchant_identifier'   => $this->getMerchantIdentifier(),
            'access_code'           => $this->getAccessCode(),
            'merchant_reference'    => $this->getMerchantReference(),
            'language'              => $this->getLanguage(),
            'service_command'       => $this->getCommand(),
            'return_url'            => $this->getReturnUrl()
        );

        //sort parameters
        ksort($requestParams);

        foreach ($requestParams as $k => $v) {
            $shaString .= "$k=$v";
        }

        //build sha string + add sha phrase
        $shaString = $shaPhrase . $shaString . $shaPhrase;


        //build signature
        $signature = hash($this->algorithm, $shaString);

        return $signature;
    }


    /**
     * calculate returned signature
     * @return bool
     */
    public function calculateReturnSignature()
    {
        $shaPhrase = $this->getShaPhrase();
        $shaString = '';

        $requestParams =   array(
            'merchant_identifier'   => $_REQUEST['merchant_identifier'],
            'access_code'           => $_REQUEST['access_code'],
            'merchant_reference'    => $_REQUEST['merchant_reference'],
            'language'              => $_REQUEST['language'],
            'service_command'       => $_REQUEST['service_command'],
            'return_url'            => $_REQUEST['return_url']
        );

        //sort parameters
        ksort($requestParams);

        foreach ($requestParams as $k => $v) {
            $shaString .= "$k=$v";
        }

        //build sha string + add sha phrase
        $shaString = $shaPhrase . $shaString . $shaPhrase;

        //build signature
        $calculatedSignature = hash($this->algorithm, $shaString);

        //return signature
        $returnSignature = $_REQUEST['signature'];

        if($calculatedSignature == $returnSignature)
            return true;

        return false;
    }



    /**
     * get Message and response Code
     * @return string
     */
    public function getMessage()
    {
        return 'code : '.$_REQUEST['response_code'].'<br />'.'message : '.$_REQUEST['response_message'];
    }


    /**
     * Calculate purchase signature
     * @return string
     */
    public function calculatePurchaseSignature()
    {
        $shaPhrase = $this->getShaPhrase();
        $shaString = '';

        $requestParameters = array(
            'command'               => $this->getCommand(),
            'merchant_identifier'   => $this->getMerchantIdentifier(),
            'merchant_reference'    => $this->getMerchantReference(),
            'access_code'           => $this->getAccessCode(),
            'customer_email'        => $this->getCustomerEmail(),
            'currency'              => $this->getCurrency(),
            'amount'                => $this->getAmount(),
            'language'              => $this->getLanguage(),
            'customer_name'         => $this->getCustomerName(),
            'token_name'            => $this->getTokenName(),
            'return_url'            => $this->getReturnUrl()
        );

        //sort data :
        ksort($requestParameters);


        foreach ($requestParameters as $k => $v) {
            $shaString .= "$k=$v";
        }

        //build sha string + add sha phrase
        $shaString = $shaPhrase . $shaString . $shaPhrase;


        //build signature
        $signature = hash($this->algorithm, $shaString);

        return $signature;
    }


    /**
     * process payment
     */
    public function processPayment()
    {

        $requestParameters = array(
            'command'               => $this->getCommand(),
            'merchant_identifier'   => $this->getMerchantIdentifier(),
            'merchant_reference'    => $this->getMerchantReference(),
            'access_code'           => $this->getAccessCode(),
            'customer_email'        => $this->getCustomerEmail(),
            'currency'              => $this->getCurrency(),
            'amount'                => $this->getAmount(),
            'language'              => $this->getLanguage(),
            'customer_name'         => $this->getCustomerName(),
            'token_name'            => $this->getTokenName(),
            'return_url'            => $this->getReturnUrl(),
            'signature'             => $this->calculatePurchaseSignature()
        );

        //sort parameters:
        ksort($requestParameters);


        $ch = curl_init( $this->requestURL );
        # Setup request to send json via POST.
        $payload = json_encode($requestParameters);

        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        # Return response instead of printing.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        # Send request.
        $result = curl_exec($ch);
        curl_close($ch);
        # Print response.
        echo "<pre>$result</pre>";

    }




    /**
     * @return mixed
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * @param mixed $algorithm
     */
    public function setAlgorithm($algorithm)
    {
        if($algorithm == 'sha128')
            $this->algorithm = 'sha1';
        else
            $this->algorithm = $algorithm;
    }


    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getMerchantIdentifier()
    {
        return $this->merchant_identifier;
    }

    /**
     * @param mixed $merchant_identifier
     */
    public function setMerchantIdentifier($merchant_identifier)
    {
        $this->merchant_identifier = $merchant_identifier;
    }

    /**
     * @return mixed
     */
    public function getAccessCode()
    {
        return $this->access_code;
    }

    /**
     * @param mixed $access_code
     */
    public function setAccessCode($access_code)
    {
        $this->access_code = $access_code;
    }

    /**
     * @return mixed
     */
    public function getOrderDescription()
    {
        return $this->order_description;
    }

    /**
     * @param mixed $order_description
     */
    public function setOrderDescription($order_description)
    {
        $this->order_description = $order_description;
    }

    /**
     * @return mixed
     */
    public function getMerchantReference()
    {
        return $this->merchant_reference;
    }

    /**
     * @param mixed $merchant_reference
     */
    public function setMerchantReference($merchant_reference)
    {
        $this->merchant_reference = $merchant_reference;
    }

    /**
     * @return mixed
     */
    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    /**
     * @param mixed $customer_email
     */
    public function setCustomerEmail($customer_email)
    {
        $this->customer_email = $customer_email;
    }

    /**
     * @return mixed
     */
    public function getCustomerIp()
    {
        return $this->customer_ip;
    }

    /**
     * @param mixed $customer_ip
     */
    public function setCustomerIp($customer_ip)
    {
        $this->customer_ip = $customer_ip;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param mixed $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->return_url;
    }

    /**
     * @param mixed $return_url
     */
    public function setReturnUrl($return_url)
    {
        $this->return_url = $return_url;
    }


    /**
     * @return mixed
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * @param mixed $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }                  //test or production environment



    /**
     * @return mixed
     */
    public function getRequestURL()
    {
        return $this->requestURL;
    }

    /**
     * @param mixed $requestURL
     */
    public function setRequestURL($requestURL)
    {
        $this->requestURL = $requestURL;
    }



    /**
     * @return mixed
     */
    public function getShaPhrase()
    {
        return $this->sha_phrase;
    }

    /**
     * @param mixed $sha_phrase
     */
    public function setShaPhrase($sha_phrase)
    {
        $this->sha_phrase = $sha_phrase;
    }



    /**
     * @return mixed
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @param mixed $customer_name
     */
    public function setCustomerName($customer_name)
    {
        $this->customer_name = $customer_name;
    }

    /**
     * @return mixed
     */
    public function getTokenName()
    {
        return $this->token_name;
    }

    /**
     * @param mixed $token_name
     */
    public function setTokenName($token_name)
    {
        $this->token_name = $token_name;
    }


}