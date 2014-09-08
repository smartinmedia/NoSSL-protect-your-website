<?php

define('NOSSL_DEBUGGING', false); //This will work through the entire PHP. Set to "false" to not get any messages from debecho, etc!!

$nossl_config = array(
        'version'       =>   '1.1',
        'auto_encryption' => 1, // 1= on (forms are automatically encrypted, 0 = off, the programmer has to do all the encryption and decryption himself)
        'directions'    =>  1,   //Direction of encryption: 1 = one-way encryption only from client to browser, 2 = two-way encryption also back from server to client
        'private_key_auto_change'      => 'on', //The private key will be changed automatically in the time interval given above // "ON" = on, "OFF" = off
        'private_key_change_interval'   =>  24*60*60, // In seconds: every how many seconds should the key be changed? Recommended 1 day = 24*60*60. Should not drop too low as only the last key is stored for browsers that still have a session. 
         'message_expirationtime'       => 30,   //After what time does the message expire between client and server in SECONDS? This is to protect against replay-attacks. Standard is 30 seconds
         'allow_resend'             => 0,  //0 = False by default: If 1 = true, then an encrypted message may be resent: this could be a security problem
         'babel'              => 1, //0 = off, 1 = on
); 




?>