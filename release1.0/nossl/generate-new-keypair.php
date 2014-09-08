
<html>
    <head>
    <style>
         body{
                font-family: Courier;
             }
           .important {
               font-weight: bold;
               font-size: 1.2em;
           }
           .warning{
               color:red;
               font-weight: bold;
               font-size: 1.2em;

           }
           #content{
               width:650px;
               margin-left:auto;
               margin-right:auto;
           }
           #footer{
               width:650px;
               margin-left:40%;
               margin-right:50%;
               position: absolute;
           }
    </style>
    <title>NoSSL - Generate Server Key-Pair</title></head>
    <body>
       <div id="content">
       <h2><img src="./images/nossl-logo.gif" border="" height="" style="position:relative;top:10px;" width="120" alt="NoSSL" /> - Generate a server key pair</h2>
<?php
set_time_limit (600); //Important: Time limit in seconds, else the script may time out with big keys!
error_reporting(E_ALL);

set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
//require_once('Net/SSH2.php');
require_once('./phpseclib/Crypt/RSA.php');
require_once('./NoSSL.class.php');

function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


if (file_exists(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_privatekey.php') || file_exists(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_publickey.php')){
    ?>
        <p><span class="warning">For security reasons, you can only generate a new key pair, when you have deleted the files of the previous key pair.</span></p>
        <p>The key files can be found in the directory /nossl/nossl_config: RSA_privatekey.php and RSA_publickey.php.</p>
    <?php 
}

else {
    



if (isset ($_REQUEST)){
    if (isset($_REQUEST['generate'])){
            if($_REQUEST['keysize']=="1024")
                    $keylength = 1024;
            else if ($_REQUEST['keysize']=="4096")
                    $keylength = 4096;
            else    $keylength = 2048;
            
            
            if (file_exists(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_privatekey.php')){
                require_once(rtrim(dirname(__FILE__),'/\\').'/nossl_config/RSA_privatekey.php');
            }
            
            if (isset($nossl_rsa_privatekey)){
                $nossl = new NoSSL($nossl_rsa_privatekey);    
            }
            else $nossl = new NoSSL();
            
            
            
            $time_start = microtime_float();
        
            $nossl->createNewRSAKey($keylength); //A new RSA key is generated and stored in /nossl/nossl_config/RSA_privatekey.php and RSA_publickey.php
            $time_end = microtime_float();
            echo "<br />Time to create the key: ".number_format((float)($time_end-$time_start),2)." seconds.";
            echo "<br /><br /><span style=\"color:green;\"><strong>The key pair was created successfully and stored to your server on /nossl/nossl_config. The keys are not shown here as this would mean a transfer from server to client, which is not secure. If you want to keep a copy, just save the file /nossl/nossl_config privatekey.php! However, if you ever loose the key, you can just create a new one with this script.</strong></span>";

            
            
    }
}



?>

        <p>With this script, you can create a RSA public / private key-pair. 2048 bit are recommended.</p>
        <p><span class="warning">WARNING AND READ THIS CAREFULLY!</span></p>
        <ul>
        <li>The key-pair, which is generated here, will be stored in the NoSSL config-directory on your server (/nossl/nossl_config). You have to protect this directory from outside access, else 
        you will compromise the security of the whole NoSSL system! There is a .htaccess file with "deny from all", but please check for yourself!</li>
        <li>The longer the key, the longer the time until the key is generated. On average systems, a 2048 bit key will take approximately 5 seconds, a 4096 bit key will easily take a minute. So please be patient! You will receive a response from this script, if the keys were generated successfully!</li>
        <li>You do not need to backup the keys. If you reinstall the server or loose the key, just generate a new pair with this script.</li>
       
        </ul>

      <form method="post" action="generate-new-keypair.php">

        <br/> 
        Key-Size (strength)
        <br />
        <input type="radio" name="keysize" value="1024"/> 1024 Bit <br />
        <input type="radio" name="keysize" value="2048" checked="checked"/> 2048 Bit (recommended)
        <br />
        <input type="radio" name="keysize" value="4096" /> 4096 Bit (slower)
         <br /><br />
        <input style="margin-bottom:30px;" type="submit" name="generate" class="important" value="Generate a new RSA-key pair (will be stored in NoSSL config-directory)"/>
        <br/><br/>
      </form><br/><br/>


      
    




<?php 

}
 ?>
     
    </div>
    <div id="footer">
        <span><a href="http://www.nossl.net">NoSSL</a> &copy; <a href="http://www.smartinmedia.com">Smart In Media 2013</a></span>
    </div>
    
    
  </body>

 </html>