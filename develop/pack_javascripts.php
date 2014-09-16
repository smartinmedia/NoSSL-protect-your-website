<?php

$intro_license_string = "/*\n########################################################################################\n
## NoSSL V1.2 - Encryption between browser and server\n
########################################################################################\n
## Copyright (C) 2013 - 2014 Smart In Media GmbH & Co. KG\n
##\n
## http://www.nossl.net\n
##\n
########################################################################################\n
\n
THIS PROGRAM IS LICENSED FOR PRIVATE USE UNDER THE GPL LICENSE\n
\n
FOR COMMERCIAL USE, PLEASE INQUIRE THROUGH www.nossl.net\n
\n
########################################################################################\n*/\n\n\n";



$js =
"json2.js
storage-wojo.js
fortuna.js
crypto.getRandomValues.js
Uint8Array.js
map-list-attributes.js
jsbn.js
jsbn2.js
prng4.js
rng.js
rsa.js
rsa2.js
SHA1.js
aes-js-SIM.js
nossl.class.js
nossl_start.js";

$js_array = preg_split('/\n|\r/', $js, -1, PREG_SPLIT_NO_EMPTY);

$complete_javascript = '';
foreach ($js_array as $value){
    $complete_javascript .= file_get_contents('./nossl/javascript/'.$value)."\r\n\r\n";
    echo "<br />Included: ".$value;
}
file_put_contents('./nossl/javascript/nossl_start.min.js', $intro_license_string.$complete_javascript);

echo "<br /><br /><br />DONE!";

?>