<?php

define('NOSSL_DEBUGGING', false); //This will work through the entire PHP. Set to "false" to not get any messages from debecho, etc!!

$nossl_config = array(
        'version'       =>   '1.2',
        'auto_encryption' => 1, // 1= on (forms are automatically encrypted, 0 = off, the programmer has to do all the encryption and decryption himself)
        'directions'    =>  1,   //NOT SUPPORTED YET, JUST LEAVE AT "1". / Direction of encryption: 1 = one-way encryption only from client to browser, 2 = two-way encryption also back from server to client
        'private_key_auto_change'      => 'on', //The private key will be changed automatically in the time interval given above // "ON" = on, "OFF" = off
        'private_key_change_interval'   =>  24*60*60, // In seconds: every how many seconds should the key be changed? Recommended 1 day = 24*60*60. Should not drop too low as only the last key is stored for browsers that still have a session. 
         
         'babel'              => 0 //0 = off, 1 = on
); 




?>