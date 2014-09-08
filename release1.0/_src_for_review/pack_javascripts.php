<?php

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
file_put_contents('./packed_javascript.js', $complete_javascript);

echo "<br /><br /><br />DONE!";

?>