<?php
/*
########################################################################################
## NoSSL(TM) V0.9Prerelease beta - Security for your website                          ##
########################################################################################
##  Copyright (C) 2014 Smart In Media GmbH & Co. KG                                   ##
##  http://www.nossl.net                                                              ##
##                                                                                    ##
##  THIS VERSION IS NOT FOR PRODUCTIVE USE! MAY CONTAIN SECURITY FLAWS!!!             ##
##                                                                                    ##
########################################################################################

    This program is free software ONLY FOR PRIVATE / NON-COMMERCIAL USE: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or any later version.
    
    F O R   C O M M E R C I A L     U S E    P L E A S E     I N Q U I R E!!

1. YOU MUST NOT CHANGE THE LICENSE FOR THE SOFTWARE OR ANY PARTS HEREOF! IT MUST REMAIN AGPL.
2. YOU MUST NOT REMOVE THIS COPYRIGHT NOTES FROM ANY PARTS OF THIS SOFTWARE!
3. NOTE THAT THIS SOFTWARE CONTAINS THIRD-PARTY-SOLUTIONS THAT MAY EVENTUALLY NOT FALL UNDER (A)GPL!
4. PLEASE READ THE LICENSE OF THE CUNITY SOFTWARE CAREFULLY!

	You should have received a copy of the GNU Affero General Public License
    along with this program (under the folder LICENSE).
	If not, see <http://www.gnu.org/licenses/>.

   If your software can interact with users remotely through a computer network,
   you have to make sure that it provides a way for users to get its source.
   For example, if your program is a web application, its interface could display
   a "Source" link that leads users to an archive of the code. There are many ways
   you could offer source, and different solutions will be better for different programs;
   see section 13 of the GNU Affero General Public License for the specific requirements.

   #####################################################################################
   */
class NoSSL{
    
    private $rsa;
    private $rsa_PK;//Will store the RSA Private Key
    
    function __construct($nossl_rsa_privatekey=""){
        if ($nossl_rsa_privatekey!=''){//This could be empty, if there is no public-key-file and it has to be generated at the beginning of the nossl_start.php
            $this->rsa_PK = $nossl_rsa_privatekey;
        }
        $this->rsa = new Crypt_RSA(); 
    }
    
    
     public function generateSessionID(){
        $_SESSION['nossl_session_id'] = time().'_'.substr(SHA1(crypt_random_string(10)),10,10);
     }
     
     public function getNewPHPSessionID(){
        return time().'_'.substr(SHA1(crypt_random_string(10)),10,10);
     }
      
     
     public function decrypt($package){
        //echo "package: ".$package;
        //This function is the easiest to use. Just decrypt the entire NoSSL-package
         if (strpos($package, '@NoSSL_Package_begin@')===false) {echo "This is not a valid NoSSL message"; return false;} //If this is not a valid package
         $content = $this->unarmorPackage($package);
         //echo "<br />ETR: ".$content['ETR'].' PTR: '.$content['PTR']." MessageKey: ".$content['MessageKey'];
         //First test, if the decryption is working correctly, i. e. message not hampered
         if ($content['MsgType']=='RSA'){
            if ($this->decryptRSA($content['ETR'], $this->rsa_PK)==$content['PTR']) {

            };
            $aes_key = $this->decryptRSA($content['MessageKey'], $this->rsa_PK);
            $_SESSION['nossl_AESKey'] = $aes_key;       
         }
         else{
             $aes_key = $_SESSION['nossl_AESKey'];
             //echo "AES Key is: ".$aes_key;
         }
         
         //This is the AES Key, armored
         
         // echo "<br /><br />AES KEY: ".$aes_key."<br /><br />";
         if ($this->AESDecrypt($content['ETA'], $aes_key)==$content['PTR']) {
            $dec_message = json_decode($this->AESDecrypt($content['Message'], $aes_key), true);
            //This is the message structure://{'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}
            if (isset($_SESSION['nossl_AESKey'])){//If the AES Key is already set, then there MUST be a Session ID already on the client side.
                if ($dec_message['SessionID'] != $_SESSION['nossl_session_id']){
                    //die ('The client does not have the correct Session ID. Possible security risk');
                }
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
        echo "  eta decrypted: ".$this->AESDecrypt($content['ETA'], $aes_key);
        echo  "  ptr: ".$content['PTR'];
        echo "Wrong AES Key, cant decrypt";     
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
     
     
     public function unarmorPackage($string){
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
        return $content;                   
     }
     
     //To get the text from the AES-Key in the message, then de-base64 to bytes
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
        $regexp='/@NoSSL_MsgType_begin@([\s\S]*)@NoSSL_MsgType_end@@/';
        $temp = array();
        preg_match($regexp, $string, $temp);
        return $temp[1];
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
    
    public function createNewRSAKey($keylength){
        $this->rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
        $this->rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
        $genkey = $this->rsa->createKey($keylength);
        $pub_rsa_key = chunk_split($this->publicRSAKeyToHex($genkey['privatekey']), 65);
        file_put_contents(rtrim(dirname(__FILE__),'/\\')."/nossl_config/RSA_privatekey.php", "<?php\n//NoSSL Private RSA Key - PROTECT THIS FILE SO THAT NO ONE ACCESSES IT FROM OUTSIDE! Do not share this file with others, else the NoSSL security is hampered!\n\n\$nossl_rsa_privatekey='".$genkey['privatekey']."';\n?>");    
        file_put_contents(rtrim(dirname(__FILE__),'/\\')."/nossl_config/RSA_publickey.php", "<?php\n//NoSSL Public RSA Key - This is the public RSA key, which should be integrated in your javascript. You can freely share!\r\n\r\n\$nossl_rsa_publickey='@NoSSL_RSAKey_begin@\r\n".$pub_rsa_key."@NoSSL_RSAKey_end@';\r\n?>");
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