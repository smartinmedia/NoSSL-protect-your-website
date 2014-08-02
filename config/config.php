<?php

$nossl_config = array();
$nossl_config['version'] = '1.1';
$nossl_config['message_expirationtime'] = 30; //After what time does the message expire between client and server in SECONDS? This is to protect against replay-attacks. Standard is 30 seconds
$nossl_config['allow_resend'] = 0; //0 = False by default: If 1 = true, then an encrypted message may be resent: this could be a security problem




?>