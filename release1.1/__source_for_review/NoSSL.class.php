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

class NoSSL{
    
    //global $nossl_last_rsa_privatekey;
    private $rsa;
    private $rsa_PK;//Will store the RSA Private Key
    private $rsa_last_PK; //This is the last private key, before the server changed it. This is important to store, if a browser has just started a session, encrypts something and in between, the server changes the private key.
    
    function __construct($nossl_rsa_privatekey=NULL){
        if ($nossl_rsa_privatekey!==NULL){//This could be empty, if there is no public-key-file and it has to be generated at the beginning of the nossl_start.php
            $this->rsa_PK = $nossl_rsa_privatekey['current_rsa_privatekey'];
            $this->rsa_PK_timestamp = $nossl_rsa_privatekey['current_rsa_timestamp'];            
            $this->rsa_last_PK = $nossl_rsa_privatekey['last_rsa_privatekey'];
            $this->rsa_last_PK_timestamp = $nossl_rsa_privatekey['last_rsa_timestamp'];
            $this->secret =         substr(sha1($this->rsa_PK.strval($this->rsa_PK_timestamp)), 0, 10); //This is a 10 digit secret, only known to the server. It can be used for making the files/folder of the sessions private
            $this->secret_last =    substr(sha1($this->rsa_last_PK.strval($this->rsa_last_PK_timestamp)), 0, 10);
        }
        $this->rsa = new Crypt_RSA();
    }
    
    
    public function debecho($string){
        if (NOSSL_DEBUGGING ===true ){
            echo $string;
        }
    }

    
     public function generateSessionID(){
        $_SESSION['nossl_session_id'] = time().'_'.substr(sha1(crypt_random_string(10)),10,10);
     }
     
     public function getNewPHPSessionID(){
        return time().'_'.substr(SHA1(crypt_random_string(10)),10,10);
     }
      
     
     public function decrypt($package){
        //echo "package: ".$package;
        //This function is the easiest to use. Just decrypt the entire NoSSL-package
         $copy_clientid = 0; //If this will be set to 1, then we need to copy the client ID from the old key-folder to the new key-folder
         if (strpos($package, '@NoSSL_Package_begin@')===false) {echo "<br />This is not a valid NoSSL message"; return false;} //If this is not a valid package
         $content = $this->unarmorPackage($package);
         //echo "<br />ETR: ".$content['ETR'].' PTR: '.$content['PTR']." MessageKey: ".$content['MessageKey'];
         //First test, if the decryption is working correctly, i. e. message not hampered
         if ($content['MsgType']=='RSA'){
            if ($this->decryptRSA($content['ETR'], $this->rsa_PK)!=$content['PTR']) {
                $this->debecho("<br />I am now here in decrypt, where the decrypt does not work because of failed decryption test!");
                //$this->rsa_PK = $this->rsa_last_PK;
                if (!$this->decryptRSA($content['ETR'], $this->rsa_last_PK)==$content['PTR']) {//Also the old key is wrong. This means, the client has either a very old key or no valid key
                    die ('<br />NoSSL decryption error!');
                }
            }
            $aes_key = $this->decryptRSA($content['MessageKey'], $this->rsa_PK);
            $_SESSION['nossl_AESKey'] = $aes_key;       
         }
         else{
             $aes_key = $_SESSION['nossl_AESKey'];
             //echo "AES Key is: ".$aes_key;
         }
         //This is the AES Key, armored
         if ($this->AESDecrypt($content['ETA'], $aes_key)==$content['PTR']) {
            $dec_message = json_decode($this->AESDecrypt($content['Message'], $aes_key), true);
            $dec_clientid = $this->AESDecrypt($content['ClientID'], $aes_key);
            $dec_messageid = $this->AESDecrypt($content['MessageID'], $aes_key); 
            $this->debecho ("<br />dec_clientid: ".$dec_clientid."<br />dec_messageid: ".$dec_messageid);
            if (!$this->checkMessageID($dec_clientid, $dec_messageid)) {
                $this->debecho('<br />False Message ID checked');
                return false;
            }
            else {$this->saveMessageID($dec_clientid, $dec_messageid);} //Save this message ID 
            
            //This is the message structure://{'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}
            if (isset($_SESSION['nossl_AESKey'])){//If the AES Key is already set, then there MUST be a Session ID already on the client side.
            }
            
             //Store the AESKey in the session
            $_SESSION['nossl_AESKey'] = $aes_key;
            if (isset ($_SESSION['nossl_used_message_ids'][$dec_message['MsgID']])){
                //die('<br />This message ID has been used before. Possible security risk.<br />Message id: '.$_SESSION['nossl_used_message_ids'][$dec_message['MsgID']].' Msg:'.$dec_message["MsgID"]);
            }
            $_SESSION['nossl_used_message_ids'][$dec_message['MsgID']]=1;//Save this message ID in the array
       
            //echo " dec message ".$dec_message['Message'];
            return $dec_message['Message'];    
         }
        else {
        echo "<br />Wrong AES Key, cant decrypt";     
        return false;}
         
         //if ($this->AESDecrypt($content['ETA'])==$content['PTR']) echo "<br />Super, the decryption worked!";
     }

    public function encrypt($plaintext){
        global $nossl_config;
        if(!isset($_SESSION['nossl_AESKey'])) die ('<br />NoSSL error: No AES Key defined! Cannot encrypt');
         //Steps: 1. Get NoSSL session ID, 2. Get timestamp, 3. Create MsgID, 4. Have message ready 
         //{'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}
        $pt = substr(SHA1(crypt_random_string(10)),10,10); //Random string to be encode as plaintext - test
        $eta = $this->AESEncrypt($pt,$_SESSION['nossl_AESKey']);
        $ciphertext = '{"SessionID":"'.$_SESSION["nossl_session_id"].'", "Timestamp":"'.time().'", "MsgID":"'.$this->getNewPHPSessionID().'", "Allow_resend":"'.(string)$nossl_config['allow_resend'].'", "Message_Expirationtime":'.(string)$nossl_config['message_expirationtime'].', "Message":"'.$plaintext.'"}';
        $ciphertext = $this->AESEncrypt($ciphertext, $_SESSION['nossl_AESKey']);
        $armored_message =      '@NoSSL_Package_begin@---'
        /*Version*/             .'@NoSSL_Version_begin@'.$nossl_config['version']
                                .'@NoSSL_Version_end@'
        /*MsgType*/             .'@NoSSL_MsgType_begin@'.'AES'.'@NoSSL_MsgType_end@'
                                .'@NoSSL_PTR_begin@'.$pt.'@NoSSL_PTR_end@' //Plain Test RSA, e. g. 10 random characters begins / ends here
                                .'@NoSSL_ETA_begin@'.$eta.'@NoSSL_ETA_end@'//Encrypted Test AES, if the decryption on AES works
                                .'@NoSSL_Message_begin@'.$ciphertext.'@NoSSL_Message_end@'
                                .'---@NoSSL_Package_end@';
        return $armored_message;
    }
     
     
    public function decryptForm(&$ref){ //&$ref stands for either POST or GET
        global $nossl;
        
        foreach($ref as $key => &$value){
            if (is_string($value)){
                if (strpos($value, '@NoSSL_Package_begin@')!==false) {
                    $value = $nossl->decrypt($value);
                    $_REQUEST[$key] = $value;
                }
            }
            else if (is_array($value)){
                foreach ($ref[$key] as $k2 => &$val2){
                    if (strpos($val2, '@NoSSL_Package_begin@')!==false) {
                        $val2 = $nossl->decrypt($val2);
                        $_REQUEST[$key][$k2] = $val2;
                    }
                }
            }
        }

    }
     
     
     //Make a new random Client ID and save it
     public function getNewClientID(){
         $clientid = 'client_'.substr(sha1(strval(microtime()).strval(rand(0, 999999))), rand(1,15), 15); //Client ID: client_<15 number string>, Get random 10-sha-string as client id
         $filename = __DIR__.'/nossl_sessions/key_'.$this->rsa_PK_timestamp.'/'.$this->secret.'_'.$clientid.'.php';
         if (file_exists($filename)){//Check that this ClientID does not exist yet: highly unlikely, but check
                $this->getNewClientID();             
         }
         else {
            file_put_contents($filename,' ',LOCK_EX);//Write a file with the clientID
            return $clientid;       
         }
     }
     //Save the message ID and check that it has not been used before
     private function saveMessageID($clientid, $messageid){
        $timestamp = $this->rsa_PK_timestamp;
        $filename = __DIR__.'/nossl_sessions/key_'.$timestamp.'/'.$this->secret.'_'.$clientid.'.php';
        if (!$this->checkClientID($clientid)){
            $this->debecho('<br />False Client ID checked');
            return false;
        }
        file_put_contents($filename, sha1($messageid.$this->secret)."\n", FILE_APPEND | LOCK_EX); //Write message ID into file with SHA1 and salted with the secret
        return true;
     }
     //Is 
     private function checkClientID($clientid){
            $timestamp = $this->rsa_PK_timestamp;
            $filename = __DIR__.'/nossl_sessions/key_'.$timestamp.'/'.$this->secret.'_'.$clientid.'.php';
            if($timestamp == 0 || !file_exists($filename) || strpos($clientid, 'client_')===FALSE){//Is the client ID set in our nossl_session folder? Does the client ID contain "client_"?, because else a client could forge a new clientID
                die('<br />The client sent a message again. This is not allowed with NoSSL!');
                return false;
            }
            return true;       
     }
    
     //Is the message ID unique (true) or has it been used before (false)
     private function checkMessageID($clientid, $messageid){
        $this->debecho('<br />Message ID is: '.$messageid.' clientID: '.$clientid);
        //$timestamp = $this->rsa_PK_timestamp;
        $filename = __DIR__.'/nossl_sessions/key_'.$this->rsa_PK_timestamp.'/'.$this->secret.'_'.$clientid.'.php';
        $filename_old_key = __DIR__.'/nossl_sessions/key_'.$this->rsa_last_PK_timestamp.'/'.$this->secret_last.'_'.$clientid.'.php';
        if($this->rsa_PK_timestamp == 0 || (!file_exists($filename) && !file_exists($filename_old_key)) || strpos($messageid, 'message_')===FALSE || strpos($clientid, 'client_')===FALSE){//Is the client ID set in our nossl_session folder? Does the client ID contain "client_" and the messageID "message_"?, because else a client could forge a new clientID
                $this->debecho('<br />Timestamp 0 OR file does not exist OR missing "message_" OR missing "client_"');
                return false;
        }
        if (!file_exists($filename)){
                //Now move the old Client ID file to the new key folder
                copy ($filename_old_key, $filename);
                unlink ($filename_old_key);
                
        }
        $content = file_get_contents($filename);
        $content_arr = explode("\n", $content);
        foreach($content_arr as $value){
            if (trim($value)=='') continue;
            $this->debecho ("<br />The read message ID from file (sha1ed) is: ".$value." and the sha1ed current msgID is: ".sha1($messageid.$this->secret));
            if (trim($value) == sha1($messageid.$this->secret)){ //Found a message ID that matches the current message ID --> possible security attack!
                $this->debecho('<br />False Message ID  ID checked');
                return false;
            }
        }
        return true;
     } 
     
     
     private function unarmorPackage($string){
        $regexp='/@NoSSL_Package_begin@([\s\S]*)@NoSSL_Package_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        $btw = $temp[1];
        $content = array();
        $content['MessageKey'] = $this->unarmorMessageKey($btw);
        $content['RSAKey'] = $this->unarmorRSAKey($btw);
        $content['Message'] = $this->unarmorMessage($btw);
        $content['PTR'] = $this->unarmorPTR($btw);
        $content['ETR'] = $this->unarmorETR($btw);
        $content['ETA'] = $this->unarmorETA($btw);
        $content['MsgType'] = $this->unarmorMsgType($btw);
        $content['ClientID'] = $this->unarmorClientID($btw);
        $content['MessageID'] = $this->unarmorMessageID($btw);
        return $content;                   
     }
     
     //To get the text from the AES-Key in the message, then de-base64 to bytes
     
     // The \s\S means "match all characters, also newlines, etc!!"
     private function unarmorAESKey($string){
        $regexp='/@NoSSL_AESKey_begin@([\s\S]*)@NoSSL_AESKey_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     
     
     private function unarmorMessageKey($string){
        if (strpos($string, '@NoSSL_MessageKey_begin@')===false) {return false;}
        $regexp='/@NoSSL_MessageKey_begin@([\s\S]*)@NoSSL_MessageKey_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     
     private function unarmorRSAKey($string){                                   
        if (strpos($string, '@NoSSL_RSAKey_begin@')===false) {return false;}
        $regexp='/@NoSSL_RSAKey_begin@([\s\S]*)@NoSSL_RSAKey_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     private function unarmorMessage($string){
        $regexp='/@NoSSL_Message_begin@([\s\S]*)@NoSSL_Message_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     private function unarmorPTR($string){
        $regexp='/@NoSSL_PTR_begin@([\s\S]*)@NoSSL_PTR_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     private function unarmorETR($string){
        if (strpos($string, '@NoSSL_ETR_begin@')===false) {return false;}
        $regexp='/@NoSSL_ETR_begin@([\s\S]*)@NoSSL_ETR_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     private function unarmorETA($string){
        $regexp='/@NoSSL_ETA_begin@([\s\S]*)@NoSSL_ETA_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     private function unarmorMsgType($string){
        $regexp='/@NoSSL_MsgType_begin@([\s\S]*)@NoSSL_MsgType_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     
     private function unarmorClientID($string){
        $regexp='/@NoSSL_ClientID_begin@([\s\S]*)@NoSSL_ClientID_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     
     private function unarmorMessageID($string){
        $regexp='/@NoSSL_MessageID_begin@([\s\S]*)@NoSSL_MessageID_end@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
     }
     
     //Function to delete the old session folder of the key older than the last PK
     private function deleteOldSessionFolder(){
        if (is_dir(__DIR__.'/nossl_sessions/key_'.strval($this->rsa_last_PK_timestamp))){
            $files = glob(__DIR__.'/nossl_sessions/key_'.strval($this->rsa_last_PK_timestamp.'/*')); // get all file names
            foreach($files as $file){ // iterate files
              if(is_file($file))
                unlink($file); // delete file
            }
            rmdir(__DIR__.'/nossl_sessions/key_'.strval($this->rsa_last_PK_timestamp));
        }            
     }
     
     
     
    
     public function publicRSAKeyToHex($privatekey) {
        $this->rsa->loadKey($privatekey);
		$raw = $this->rsa->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_RAW);
		return $raw['n']->toHex();
	}
	
	public function decryptRSA($encryptedstring, $privatekey) {
		$encryptedstring=pack('H*', $encryptedstring);
                $this->rsa->loadKey($privatekey);
		$this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		return $this->rsa->decrypt($encryptedstring);
	}
    
    
    public function strToHex($string){
        $hex='';
        for ($i=0; $i < strlen($string); $i++)
        {
            $temp = dechex(ord($string[$i]));
            if (strlen($temp)!=2) $temp="0".$temp; //If the length of the hex number is only 1, then we put a 0 in front of it!
            $hex .= $temp;
        }
        return $hex;
    }

    public function hexToStr($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2)
        {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
    /*
     * Create a new RSA Key
     * 
     */
    public function createNewRSAKey($keylength){
        if (isset($this->rsa_last_PK_timestamp) && $this->rsa_last_PK_timestamp!=0){
            $this->deleteOldSessionFolder();
        }
        if (isset($this->rsa_PK) && $this->rsa_PK != ''){
            //$this->debecho('<br />I am in isset rsa_PK');
            $old_rsa_PK = $this->rsa_PK;
            $old_timestamp = $this->rsa_PK_timestamp;
        }
        else {
            $old_rsa_PK = '';
            $old_timestamp = 0;
        }
        
        $cur_timestamp = time();
        mkdir(__DIR__.'/nossl_sessions/key_'.strval($cur_timestamp));
        $this->rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
        $this->rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
        $genkey = $this->rsa->createKey($keylength);
        $pub_rsa_key = chunk_split($this->publicRSAKeyToHex($genkey['privatekey']), 65);
        
        file_put_contents(__DIR__.'/nossl_config/RSA_privatekey.php', "<?php\n//NoSSL Private RSA Key - PROTECT THIS FILE SO THAT NO ONE ACCESSES IT FROM OUTSIDE! Do not share this file with others, else the NoSSL security is hampered!\n\n"
        ."\$nossl_rsa_privatekey = array('current_rsa_privatekey' => '"
        .$genkey['privatekey']."',\n\n"
        ."//The current_rsa_timestamp and the last_rsa_timestamp store the Unix-time time(), when the current/last private key was generated. This is important, when the private key is renewed every day or so. The last private key has to be stored here, so that the server still has the right key present for browsers, which dont have the changed key yet. Supports some kind of perfect forward secrecy\n"
        ."'current_rsa_timestamp' => ".$cur_timestamp.",\n\n"
        ."'last_rsa_privatekey' => '".$old_rsa_PK."',\n\n"
        ."'last_rsa_timestamp' => ".$old_timestamp.");\n\n"
        ."?>");
        
            
        file_put_contents(__DIR__."/nossl_config/RSA_publickey.php", "<?php\n//NoSSL Public RSA Key - This is the public RSA key, which should be integrated in your javascript. You can freely share!\r\n\r\n\$nossl_rsa_publickey='@NoSSL_RSAKey_begin@\r\n".$pub_rsa_key."@NoSSL_RSAKey_end@';\r\n?>");
        
        
    }
    
    public function AESDecrypt($encryptedstring, $AESKey){
        if (!$AESKey) die ('<br />AES Key is not defined!');
        $passbytes = $this->base64ToPassbytes($this->unarmorAESKey($AESKey)); 
        return AesCtr::decrypt($encryptedstring, $passbytes, 256);
    } 
    
    public function AESEncrypt($plaintext, $AESKey){
        if (!$AESKey) die ('<br />AES Key is not defined!');
        $passbytes = $this->base64ToPassbytes($this->unarmorAESKey($AESKey));
        return AesCtr::encrypt($plaintext, $passbytes, 256);
    }
    
    public function base64ToPassbytes($base64string){
        $key = base64_decode($base64string);
        $passbytes = array();
        for ($i=0; $i<strlen($key); $i++) $passbytes[$i] = ord(substr($key,$i,1)) & 0xff;
        return $passbytes;        
    }
    
}


        
?>