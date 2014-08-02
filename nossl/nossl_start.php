<?php
//set_include_path(get_include_path() . PATH_SEPARATOR .  dirname(__FILE__).'phpseclib');
//require_once('Net/SSH2.php');
require_once(rtrim(dirname(__FILE__),'/\\').'/phpseclib/Crypt/RSA.php');
require_once(rtrim(dirname(__FILE__),'/\\').'/aes.class.php');
require_once(rtrim(dirname(__FILE__),'/\\').'/NoSSL.class.php');
require_once(rtrim(dirname(__FILE__),'/\\').'/nossl_config/config.php');

if (!file_exists(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_privatekey.php') || !file_exists(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_publickey.php')){
    $extra_nossl = new NoSSL();
    $extra_nossl->createNewRSAKey(2048); //A new RSA key is generated and stored in /nossl/nossl_config/RSA_privatekey.php and RSA_publickey.php
}
require_once(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_privatekey.php');
require_once(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_publickey.php');
session_name('nossl');
session_start();

/*$_SESSION[]-variables for NoSSL:
 * $_SESSION['nossl_session_id'] //contains a self-made session id, which is transported back and forth to check the user
 * $_SESSION['nossl_AESKey'] //Contains the armored AESKey
 * $_SESSION['nossl_used_message_ids'] //An array, which saves all message IDs used by the client. In the form $_SESSION['nossl_used_message_ids']['string_of_the_id']
 *   
 */

$nossl = new NoSSL($nossl_rsa_privatekey);
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


                                                                               
 //Now the variable that has to be included in every HTML page, which requires NoSSL. 
$nossl_echo_this_into_body = '<div class="nossl_serversettings" style="display:none;"><span id="nossl_rsa_key">'.$nossl_rsa_publickey.'</span><span id="nossl_servertime">@NoSSL_ServerTime_begin@'.time().'@NoSSL_ServerTime_end@</span>'.'<span id="nossl_version">@NoSSL_Version_begin@'.$nossl_config['version'].'@NoSSL_Version_end@</span></div>';
 
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


// NoSSL Handshake No. 1 is coming in
if (isset($_REQUEST['nossl_json_data1'])){
    //echo "jsondata: ".$_REQUEST['json_data1']."//END";
    $response_plain = array();
    $nossl_data_back = json_decode(($_REQUEST['nossl_json_data1']), true);
    $response_plain['task'] = '1'; //For handshake Nr. 1
    $response_plain['publickey'] =  $nossl_rsa_publickey;
    $response_plain['servertime'] = time();
    $response_plain['version'] = $nossl_config['version'];
    $response_plain['salt'] = sha1(rand(0, 999999));
    if (isset($_SESSION['nossl_session_id'])) $response_plain['sessionID_hashed'] = sha1($response_plain['salt'].$_SESSION['nossl_session_id']); //If there is a session ID present, we'll salt and SHA1 it. This is important for browsers without sessionStorage as they always need to ask, if the session is still the same as their storage does not time out with a timed out session
    else $response_plain['sessionID_hashed'] = '';
    
    $jsonData=json_encode2($response_plain);  //$status is an assoc_array with boolean "status" and string "statusmessage"
    echo $jsonData; // back to the client
    
}
// NoSSL Handshake No. 2 is coming in
else if (isset($_REQUEST['nossl_json_data2'])){
    $response_plain = array();
    $response_crypt = array();
    $nossl_data_back = json_decode(($_REQUEST['nossl_json_data2']), true);
    //By decrypting, we automatically store the AES Key that was sent encrypted by the Client via Ajax
    //echo "<br />Data-Package: ".$nossl_data_back['package'];
    $package = $nossl->decrypt($nossl_data_back['package']); //This is now the JSON object containing {'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}. With this, the AESKey, made by the client is stored in the session
    
    //Now we send back everthing from the server: the OK, the nossl-sessionID, the time again, et. The message-text does not matter
    $response_plain['task'] = '2'; //For handshake Nr. 1
    $response_plain['publickey'] =  $nossl_rsa_publickey;
    //Back to client, encrypted
    //echo "<br />DONE!";
//    echo "jsonencoded: ".json_encode2($status);
    $response_plain['sessionID'] = $nossl->encrypt($_SESSION['nossl_session_id']);  //$status is an assoc_array with
    echo json_encode2($response_plain);  
}
     

else if (isset($_REQUEST['nossl_encrypted_ajax'])){
    $response_plain = array();
    //$response_crypt = array();
    //$nossl_data_back = json_decode(($_REQUEST['nossl_encrypted_ajax']), true);
    //print_r($_REQUEST);
    //By decrypting, we automatically store the AES Key that was sent encrypted by the Client via Ajax
    //echo "<br />Data-Package: ".$nossl_data_back['package'];
    //$package = $nossl->decrypt($nossl_data_back['package']); //This is now the JSON object containing {'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}. With this, the AESKey, made by the client is stored in the session

    //echo "<br /><br />What I got here is: ".$nossl_data_back['data'];

    //Now we send back everthing from the server: the OK, the nossl-sessionID, the time again, et. The message-text does not matter
    //$response_plain['task'] = '2'; //For handshake Nr. 1
    //$response_plain['publickey'] =  $nossl_rsa_publickey;
    //Back to client, encrypted
    //echo "<br />DONE!";
//    echo "jsonencoded: ".json_encode2($status);
    //$response_plain['sessionID'] = $nossl->encrypt($_SESSION['nossl_session_id']);  //$status is an assoc_array with
    
    //echo json_encode2($_REQUEST);
}


?>