/*
 * 
 * This script is (c) by Smart In Media GmbH & Co. KG, Germany, http://www.smartinmedia.com
 * LICENSE: 
 *
 * 
 * openpgp.js-crypto.getRandomValues
=================================

OpenPGP.js currently does not support browsers, which dont have the crypto.getRandomValues() function. This adds it.

!!!! ONE IMPORTANT NOTE:
THIS SCRIPT USES THE FORTUNA-PSEUDO-RANDOM-NUMBER-GENERATOR FROM 
https://github.com/wxfz/fortuna
WE CANNOT GUARANTEE YOU THAT THIS IS CRYPTOGRAPHICALLY SAFE!!
YOU'RE DEALING ON YOUR OWN RISK WITH THAT. WE DON'T TAKE ANY LIABILITY!!!!



NoSSL
(c) 2013 by Smart In Media GmbH & Co. KG, Koeln, Germany

WE DO NOT TAKE RESPONSIBILITY FOR ANY SECURITY FLAWS / ERRORS IN THIS SCRIPT
YOU USE THIS ON YOUR OWN RISK
WE ARE NOT LIABLE FOR ANY DAMAGES; LOSS; FINANCIAL DAMAGES; PERSONAL DAMAGES; DAMAGES
TO HARDWARE; SECURITY PROBLEMS; ETC!!!


http://www.smartinmedia.com

This is how it works:

1. Implement openpgp.js in your source (please see the great openpgp.js for a detailed description and for expamples).
2. On your HTML/PHP-documents, FIRST include jquery, THEN include fortuna.js (from here), THEN include crypto.getRandomValues.js, THEN include the openpgp.js-libraries
3. Then it should work. Please note: Internet Explorer has a problem with "localStorage", if you work offline (file:///...), it only works with http://

<script src="./javascript/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="./javascript/fortuna.js" type="text/javascript" charset="utf-8"></script>
<script src="./javascript/crypto.getRandomValues.js" type="text/javascript" charset="utf-8"></script>
...then here all the openpgp.js stuff
 
 * 
 */


function nossl_msieversion() { //Check, if Internet Explorer (some number) or not (false)

        var ua = window.navigator.userAgent;
        var msie = ua.indexOf("MSIE ");

        if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer, return version number
            return(parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))));
        else                 // If another browser, return 0
            return false;
}

if (typeof window.crypto==='undefined'){
        console.log("Browser does not natively support crypto!");
          window.crypto = new Object();
    }
else console.log('Browser supports crypto');

if (typeof window.crypto.getRandomValues==='undefined') {
    //If browser does not have the crypto.getRandomValues function, we need to make our own PRNG
    console.log("Browser does not natively support crypto.getRandomValues(buf)!!");
    console.log(window.crypto);
    
    if(nossl_msieversion()!==false){ //If browser is Microsoft Internet Explorer, it does not know __proto__ --> IE really sucks...
        window.crypto.getRandomValues = function (arr){
                for (var i = 0; i < arr.length; i++) { //Go through the array
                        //var buf = new Uint8Array(1);
                        var rbyte = wxfz.fortuna.generate(1);//We now generate 1 byte
                        arr[i] = rbyte.charCodeAt(0);
        
                      /*
                        for (var i=0; i<3; i++){ //Go through the 4 bytes and concetenate them to 1 32bit-number
                            buf[0] = buf[0] + rbyte.charCodeAt(i); buf[0] = buf[0]*256;
                        }
                        buf[0] = buf[0] + rbyte.charCodeAt(3);
                        arr[i] = buf[0]; //
                     */
                        //    console.log('arr['+i+']: '+arr[i]);
                    }

        };
    }
    else { //If other browser, not IE
        window.crypto.__proto__.getRandomValues = function (arr){
            for (var i = 0; i < arr.length; i++) { //Go through the array
                //var buf = new Uint8Array(1);
                var rbyte = wxfz.fortuna.generate(1);//We now generate 1 byte
                arr[i] = rbyte.charCodeAt(0);
                
              /*  
                for (var i=0; i<3; i++){ //Go through the 4 bytes and concetenate them to 1 32bit-number
                    buf[0] = buf[0] + rbyte.charCodeAt(i); buf[0] = buf[0]*256;
                }
                buf[0] = buf[0] + rbyte.charCodeAt(3);
                arr[i] = buf[0]; //     
             */   
                //    console.log('arr['+i+']: '+arr[i]);    
            }
            
            

        };    
    }


} 




/**
 * Retrieve secure random byte string of the specified length
 * @param {Integer} length Length in bytes to generate
 * @return {String} Random byte string
 */


function openpgp_crypto_getSecureRandomOctet() {
	var buf = new Uint32Array(1);
	window.crypto.getRandomValues(buf);
	return buf[0] & 0xFF;
}

function openpgp_crypto_getRandomBytes(length) {
	var result = '';
	for (var i = 0; i < length; i++) {
		result += String.fromCharCode(openpgp_crypto_getSecureRandomOctet());
	}
	return result;
}
  