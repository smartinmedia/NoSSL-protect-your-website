<?php

/*
########################################################################################

## NoSSL V1.1 - Encryption between browser and server

########################################################################################

## Copyright (C) 2013 - 2014 Smart In Media GmbH & Co. KG

##

## http://www.nossl.net

##

########################################################################################



THIS PROGRAM IS LICENSED FOR PRIVATE USE UNDER THE GPL LICENSE



FOR COMMERCIAL USE, PLEASE INQUIRE THROUGH www.nossl.net



########################################################################################
*/

//set_include_path(get_include_path() . PATH_SEPARATOR .  dirname(__FILE__).'phpseclib');
//require_once('Net/SSH2.php');
require_once(__DIR__.'/nossl_config/config.php');
require_once(__DIR__.'/phpseclib/Crypt/RSA.php');
require_once(__DIR__.'/aes.class.php');
require_once(__DIR__.'/NoSSL.class.php');


if (!file_exists(__DIR__.'/nossl_config/RSA_privatekey.php') || !file_exists(__DIR__.'/nossl_config/RSA_publickey.php')){
    $extra_nossl = new NoSSL();
    $extra_nossl->createNewRSAKey(2048); //A new RSA key is generated and stored in /nossl/nossl_config/RSA_privatekey.php and RSA_publickey.php
}

require_once(__DIR__.'/nossl_config/RSA_privatekey.php');
require_once(__DIR__.'/nossl_config/RSA_publickey.php');


$client_needs_new_clientid = 0; //If this is 1, the client will get a new client ID

$nossl = new NoSSL($nossl_rsa_privatekey);
//$nossl->debecho('<br /><br />I received this now: '.time()."<br />"); print_r($_REQUEST);


if ($nossl_config['private_key_auto_change']=="on" && $nossl_config['private_key_change_interval'] < time() - $nossl_rsa_privatekey['current_rsa_timestamp']){ //If the time for this NOSSL key has run out, make a new one!
    //$nossl->debecho('<br />In if private_key_auto_change');
    $nossl->createNewRSAKey(2048); //A new RSA key is generated and stored in /nossl/nossl_config/RSA_privatekey.php and RSA_publickey.php    
}


if ($nossl_config['directions'] == 2){ //If the encryption should work both ways, we need sessions, else not
    session_name('nossl');
    session_start();
}



/*$_SESSION[]-variables for NoSSL:
 * $_SESSION['nossl_session_id'] //contains a self-made session id, which is transported back and forth to check the user
 * $_SESSION['nossl_AESKey'] //Contains the armored AESKey
 * $_SESSION['nossl_used_message_ids'] //An array, which saves all message IDs used by the client. In the form $_SESSION['nossl_used_message_ids']['string_of_the_id']
 *   
 */


/*
if (isset($_REQUEST['nossl_encrypted_ajax'])){
    $decrypted_form_variables = ($nossl->decrypt($_REQUEST['nossl_encrypted_ajax']));
    echo "Decrypted form var: ".$decrypted_form_variables;
    exit();
}
*/

if (isset($_REQUEST['nossl_encrypted_form_values']) || isset($_REQUEST['nossl_encrypted_ajax'])){
    $temp = array();
    if(isset($_REQUEST['nossl_encrypted_form_values'])){
        $decrypted_form_variables = ($nossl->decrypt($_REQUEST['nossl_encrypted_form_values']));
    }
    else {// IF nossl_encrypted_ajax
        $decrypted_form_variables = ($nossl->decrypt($_REQUEST['nossl_encrypted_ajax']));
        $data_back = json_decode (stripslashes($decrypted_form_variables), true);
        
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            parse_str($decrypted_form_variables, $temp);
            foreach ($temp as $key=>$value){
                $_POST[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            parse_str($decrypted_form_variables, $temp);
            foreach ($temp as $key=>$value){
                $_GET[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
}
    
/*
if (isset($_REQUEST['nossl_ajaxbridge'])){
    $data_back = json_decode (stripslashes($_REQUEST['nossl_ajaxbridge']), true);
    echo "response ist: ".$data_back['response'];    
}
*/


$temp2 = serverSettings2JSON('1');//This adds some more server variables
//Now the variable that can be included in every HTML page, which requires NoSSL. It saves 1 AJAX request.
$nossl_echo_this_into_body = '<div id="nossl_serversettings" style="display:none;">'
                            .$temp2
                            .'</div>';
 
function nossl_echo($string){
    //This is an echo for nossl. With this the programmer can replace his echos to nossl_echos, which are instantly encrypted.
    global $nossl;
    if(isset($_SESSION['nossl_session_id'])){
        $temp_enc = $nossl->encrypt($string);
        echo '<span class="nossl_echo_encrypted" style="display:none;">'.$temp_enc.'</span>';    
    }
    
 }
 
 function nossl_encrypt($string){
     global $nossl;
     return $nossl->encrypt($string);
 }
 
 function nossl_decrypt($string){
     global $nossl;
     return $nossl->decrypt($string);
 }
 
 //Generate a new NoSSL Session ID
if (!isset($_SESSION['nossl_session_id']) || $_SESSION['nossl_session_id']==''){
     $nossl->generateSessionID(); 
 }

//If not yet defined start this array, which contains the used message IDs 
if (!isset($_SESSION['nossl_used_message_ids'])) $_SESSION['nossl_used_message_ids'] = array(); 
  
/*
* 
* H A N D S H A K E   V I A    A J A X 
* We need to do 2 handshakes: First, the client requests the public key and second, the client sends the encrypted AES session key
* 
*/

/*
// JSON_ENCODE2 as json_encode() doesn't convert German and other characters correctly (ä ö ü ß)
*/
function json_encode2($jsonarray){
        foreach($jsonarray AS &$text)
        {
            if ($text!==true && $text!==false && !is_array($text))//If they are boolean, leave them that way
            {$text=utf8_encode($text);}
        }
        unset($text);
        $jsonarray=json_encode($jsonarray);
        return $jsonarray;
}

//The server settings, which are important for the client, are wrapped into JSON so they can be either put in the body or sent via AJAX
//Gets $handshake as a string: "1" for handshake 1 or "2" for handshake 2. Use "1" for nossl_this_into_body
function serverSettings2JSON($handshake='1'){
    global $nossl, $nossl_config, $nossl_rsa_privatekey, $client_needs_new_clientid;
    $itemsarray = array();
    $remainingLeaseTime = $nossl_rsa_privatekey['current_rsa_timestamp'] + $nossl_config['private_key_change_interval'] - time(); //What is the remaining validity time of the current RSA key? Tell it to the client so he can refresh accordingly
    //Generate a random client ID. This will be used by the client each time, when sending a message to identify and to bind a unique message ID to it
    if ($client_needs_new_clientid==1) $itemsarray['nossl_clientid'] = $nossl->getNewClientID();
    else $itemsarray['nossl_clientid'] = 'zero'; //If the client does not need a new client ID
    //$nossl->debecho('client_needs_new_clientid is: '.$client_needs_new_clientid);
    $itemsarray['nossl_salt'] = sha1(rand(0, 999999));
    if ($nossl_config['babel']==1){
        $babel = file_get_contents(__DIR__.'/nossl_config/babel.txt');
        $itemsarray['nossl_babel_switch'] = 1;
        $itemsarray['nossl_babel_content'] = base64_encode(json_encode2(preg_split('/\n|\r/', $babel, -1, PREG_SPLIT_NO_EMPTY)));
    }
    else $itemsarray['nossl_babel_switch'] = 0;
    
    
    if (isset($_SESSION['nossl_session_id'])) $itemsarray['nossl_sessionID_hashed'] = sha1($itemsarray['nossl_salt'].$_SESSION['nossl_session_id']); //If there is a session ID present, we'll salt and SHA1 it. This is important for browsers without sessionStorage as they always need to ask, if the session is still the same as their storage does not time out with a timed out session
    else $itemsarray['nossl_sessionID_hashed'] =          '';
    if ($handshake == '2'){
        $itemsarray['nossl_sessionID'] = $nossl->encrypt($_SESSION['nossl_session_id']);  //Encrypt the session variable and send it to client   
    }
    $itemsarray['nossl_task'] =                           $handshake;
    $itemsarray['nossl_salt'] =                           sha1(rand(0, 999999));
    $itemsarray['nossl_rsa_publickey'] =                  $GLOBALS["nossl_rsa_publickey"];
    $itemsarray['nossl_servertime'] =               time();
    $itemsarray['nossl_version'] =                  $nossl_config['version'];
    $itemsarray['nossl_directions'] =               $nossl_config['directions'];
    $itemsarray['nossl_remaining_lease_time'] =     $remainingLeaseTime;
    $itemsarray['nossl_auto_encryption']    =       $nossl_config['auto_encryption'];    
    
    $serverSettingsJSON = json_encode2($itemsarray);
    return $serverSettingsJSON;
}


// NoSSL Handshake No. 1 is coming in asking for the server settings and the public RSA key
if (isset($_REQUEST['nossl_json_data1'])){
    $nossl_data_back = json_decode(($_REQUEST['nossl_json_data1']), true);
    if ($nossl_data_back['need_client_id']==1){
        $client_needs_new_clientid = 1;
    }
    else $client_needs_new_clientid = 0;
    $jsonData = serverSettings2JSON("1");
    echo $jsonData; // back to the client
    
}
// NoSSL Handshake No. 2 is coming in
else if (isset($_REQUEST['nossl_json_data2'])){
    $nossl_data_back = json_decode(($_REQUEST['nossl_json_data2']), true);
    //By decrypting, we automatically store the AES Key that was sent encrypted by the Client via Ajax
    $package = $nossl->decrypt($nossl_data_back['package']); //This is now the JSON object containing {'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}. With this, the AESKey, made by the client is stored in the session
    $jsonData = serverSettings2JSON("2");
    echo $jsonData; // back to the client
      
}
     



?>