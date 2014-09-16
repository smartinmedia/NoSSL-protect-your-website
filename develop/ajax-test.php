<?php
error_reporting(-1);
require_once('./nossl/nossl_start.php');

//echo ($nossl->encrypt('<h1>This is a big Test</h1>'));

if (isset($_REQUEST['nossl_ajaxtest'])){
    $content1 = json_decode($_REQUEST['nossl_ajaxtest'], true);
    $content2 = 'You sent this: '.utf8_decode($content1['Testsendung']); 
    //$content2 = nossl_encrypt('You sent this: '.utf8_decode($content1['Testsendung']));
    $answer = array('response'=>$content2);
    die (json_encode2($answer));
    
}


if (isset($_REQUEST['KillSession'])){
    session_unset();
    echo "<br /><br />SESSION CLEAR<br /><br />";
}
                                                
if (isset($_REQUEST['password'])){
    echo "<p>REQUEST: </p>";
    print_r ($_REQUEST);
    
}
             
/*        
if (isset($_REQUEST['password'])) {
		
        echo "<br /><br />RESULT: ".$nossl->decrypt($_REQUEST['password']);
}
*/

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <style>
        .formdiv{
            width:400px;
            margin-top:20px;
            border: 2px solid #7A7AA1;
            padding:10px;
        }

        #content{
            margin-left:auto;
            margin-right:auto;
            width:750px;
            font-family:"Verdana";
        }
    </style>
    <link href="./nossl/style/nossl.css" type="text/css" rel="stylesheet" />
    <script src="./nossl/javascript/jquery110.js"></script>

<!-- This part is for browsers that are missing modern browser's functionality BEGIN -->
	<script src="./nossl/javascript/outerHTML-2.1.0.js"></script>
    <script src="./nossl/javascript/json2.js"></script>
    <script src="./nossl/javascript/storage-wojo.js"></script>
    <script src="./nossl/javascript/fortuna.js" type="text/javascript"></script>
    <script src="./nossl/javascript/crypto.getRandomValues.js" type="text/javascript"></script>
	<script src="./nossl/javascript/Uint8Array.js" type="text/javascript"></script>
<!-- This part is for browsers that are missing modern browser's functionality END -->


<!-- Some addon BEGIN -->
	<script src="./nossl/javascript/map-list-attributes.js"></script>
<!-- Some addon END -->

    
<!-- Crypto stuff  BEGIN -->    
    <script src="./nossl/javascript/jsbn.js"></script>
	<script src="./nossl/javascript/jsbn2.js"></script>
	<script src="./nossl/javascript/prng4.js"></script>
	<script src="./nossl/javascript/rng.js"></script>
	<script src="./nossl/javascript/rsa.js"></script>
	<script src="./nossl/javascript/rsa2.js"></script>
	<script src="./nossl/javascript/SHA1.js"></script>
    <script src="./nossl/javascript/aes-js-SIM.js"></script>
<!-- Crypto stuff  END -->

<!-- NoSSL stuff  BEGIN -->
	<script src="./nossl/javascript/nossl.class.js"></script>
	<script src="./nossl/javascript/nossl_auto_start.js"></script>
<!-- NoSSL stuff  end -->

    <script>

	
    function CheckInput(){
        console.log('callme executed');
        return true;    
    }
    
    function killSession(){
            nossl.cleanSessionStorage();
            return true;
    }


    $('document').ready(function(){
        
        //nossl.parseServerSettings($('#nossl_serversettings').text());
        //console.log(nossl.encrypt('This is a test')); 
       $('#ajaxtest').click(function(){
            var tester = '{"Testsendung":"Hier ist der Inhalt, mit Ümläuten..."}';
            $.ajax({
            type: "POST",
            url: "./ajax-test.php",
            data: {nossl_ajaxtest: tester},
            async: false,
            //beforeSend: function(){},
            dataType: "json",
            success: function(msg) {
                if (msg===null || msg.status === false){
                    console.log('Ajax Function error');
                }
                else {    //If everything worked OK
                    
                    var received = msg.response;
                    //var received = nossl.decrypt(msg.response);
                    $('#response').html('<span style="color:green;font-weight:bold">'+received+'</span>'); //We have to do this (cant return this) as AJAX is asynchronous
                }},
            error: function() {
                console.log('An error ocurred (AJAX ERROR)!');
                }
            });
        });


    $('#posttest').click(function(){
        var tester = '{"Testsendung":"Hier ist ein weiterer Test, diesmal mit POST"}';
        //console.log('AES Key js: '+nossl.getStuff());    
        $.post( "./ajax-test.php", {nossl_ajaxtest:tester}, function( msg ) {
            var got =  $.parseJSON(msg);
            //got.response = nossl.decrypt(got.response);
            $( "#response3" ).html('<span style="color:green;font-weight:bold">'+got.response+'</span>');
        });
    });


	});


   
	
	</script>
	<title>NoSSL demo</title>
</head>
<body>
    <div id="content">	
				<h1>NoSSL Demo</h1>
				<h2><small>Example by <a href="http://www.smartinmedia.com">Smart In Media</a></small></h2>
	
        <div class="formdiv">
			<form action="ajax-test.php" class="testclass andanother" onsubmit="return CheckInput();" method="post">
			      User name: <input type="text" name="username" /><br />
			      Password: <input type="password" name="password" /><br />
			      Textarea: <textarea name="textarea_field2">Testfeld...</textarea><br />
			       <br />

			     <input type="submit" name="test" value="Send data!"/>
            </form>
        </div>    
        <div class="formdiv">    
            <form action="ajax-test.php" class="testclass andanother" onsubmit="return killSession();" method="post">
			      Kill session
			      <input type="hidden" name="KillSession"/>
			     <input type="submit" name="test2" value="Kill session!"/>
            </form>
        </div>    
            <br /><br />
            <button id="ajaxtest">Test jQuery-Ajax</button>
            <button id="posttest">Test jQuery-Post</button>
            <br /><br />
            <div id="response" style="width:450px;margin-top:20px;border:solid 1px grey;">Waiting for Ajax</div>
            <div id="response2"></div>
            <div id="response3" style="width:450px;margin-top:20px;border:solid 1px grey;">Waiting for Post</div>
  </div>

</body>
</html>
