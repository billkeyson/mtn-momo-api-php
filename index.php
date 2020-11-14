<?php 
// import composer autoload 
require 'vendor/autoload.php';
// import request client 
require_once 'vendor/pear/http_request2/HTTP/Request2.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoMo</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>


<?php 

// http request object
$subcriptionKey = "3e5495b8aefb49459addcfa9466e7147";
$env = "sandbox";
$userRefId = "bff350dc-c479-4749-a498-fbf2c387146a";
$userApiKey = "dd9f1ad5fac44893b0afa55125f8eabe";
$callbackUri = "https://task-event.herokuapp.com/momo/mtn/callback";

// import http client 
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Momo sandbox access token request
function basicAuthFlow(){
    
    if(isset($_COOKIE["access_token"])){
        return $_COOKIE["access_token"];

    }else{
      try{

        $client = new Client(
            ['base_uri' => "https://sandbox.momodeveloper.mtn.com/"]); 
       $response =  $client->request(
            "POST",
            "collection/token/",
            ["headers"=>["Ocp-Apim-Subscription-Key"=>"3e5495b8aefb49459addcfa9466e7147",
            'Content-Type' => 'application/json',
            "Authorization"=> "Basic NzFiNGRiOGUtNGYwNS00ZGNjLTlhZTMtMjJlMjFiM2Q5MGI3OjBmY2RiNjhiMmZmYzQwYzY4NzEwMGUwYTY2OTg0YTUw"]]    
        );
        $json_to_obj = json_decode($response->getBody());
        setcookie("access_token", $json_to_obj->access_token, time() + 3600);
        return $json_to_obj->access_token;
      }
      catch(Exception $err){
        print_r($err);
        return "error";

      }
    }
}
// generate UUID-v4 for purchase ref
function generateUserRefUUID(){
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,
        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );

}

// make momo request
function sendPaymentRequest(){
global $subcriptionKey;
global $env;
global $userRefId;
global $userApiKey;
global $callbackUri;
global $authorizationToken;
global $client;


// request header
$userRefNo = generateUserRefUUID();
$headers = [
    'X-Reference-Id' => $userRefNo,
    "Ocp-Apim-Subscription-Key"=>"3e5495b8aefb49459addcfa9466e7147",
    "X-Target-Environment"=>$env,
    "Authorization"=>"Bearer ".basicAuthFlow(),
    'Content-Type' => 'application/json',
    // 'X-Callback-Url' => 'https://task-event.herokuapp.com/momo/mtn/callback',
];
// request body
$body = [
    "amount"=>$_POST['total'],
    "currency"=> "EUR",
    "externalId"=> "12345",
    "payer"=>[
      "partyIdType"=> "MSISDN",
      "partyId"=>$_POST["phone"]
    ],
    "payerMessage"=> $_POST['payee_message'],
    "payeeNote"=>$_POST['payee_message']
    ];

// request endpoint
$request = new Http_Request2('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay');
// $url = $request->getUrl();
$request->setHeader($headers);
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setBody(json_encode($body));

try
{
    $response = $request->send();

    // successful request send for momo is 202 status
      // echo "Reponse ". $response->getBody()." - status ".$response->getStatus();

    if ($response->getStatus()==202){
      // echo "Reponse ". $response->getBody()." - status ".$response->getStatus();

      // save information in database and await approval from momo callback
      // TODO-: database code......
      echo '<div class="alert alert-success alert-dismissible">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Success!</strong> Nice! Paid. Awaiting confirmation ! Ref ID-: '.$userRefNo.'
    </div>';

    }else{

      echo "Naa";
      // Failed to submit...
      // DO Sometime - - OPtional
    }

}
catch (HttpException $ex)
{
    echo $ex;
}

}
?>
    
<?php 
// Catch submit request
if(isset($_POST['momopayment'])){
  // Temp Mtn Momo access token generation very hour
  basicAuthFlow();
// initialize USER purchase request
    sendPaymentRequest();
}
?>

  
  <div class="container">
  
  <h2>Payment Form</h2>
  <form class="form-horizontal" action="" method="POST">

  <div class="form-group">
      <label class="control-label col-sm-2" for="phone">Total Cart :</label>
      <div class="col-sm-2">
                <button type="button" class="btn btn-primary" value="10" name="total">
            Total EUR:  <span class="badge badge-light">10</span>
            </button>
            <input type="hidden" value="10" name="total" />
      </div>
    </div>
    
    <div class="form-group">
      <label class="control-label col-sm-2" for="phone">Mobile Number:</label>
      <div class="col-sm-8">
        <input type="tel" maxlength="10" class="form-control" id="phone" placeholder="Enter Momo Number" name="phone">
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-sm-2" for="pwd">Message:</label>
      <div class="col-sm-8">          
        <textarea class="form-control" id="pwd" placeholder="(Optional) Add A Note" name="payee_message"></textarea>
      </div>
    </div>

    
    <div class="form-group">        
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" name="momopayment" class="btn btn-default">Submit</button>
      </div>
    </div>
  </form>
</div>


</body>
</html>