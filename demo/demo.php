<?php
//error_reporting(-1);
require_once(__DIR__.'/../lib/nossl_start.php');

//echo ($nossl->encrypt('<h1>This is a big Test</h1>'));

if (isset($_REQUEST['nossl_ajaxtest'])){
    $content1 = json_decode($_REQUEST['nossl_ajaxtest'], true);
    $content2 = 'You sent this (already decrypted): '.utf8_decode($content1['Testmsg']); 
    //$content2 = nossl_encrypt('You sent this: '.utf8_decode($content1['Testsendung']));
    $answer = array('response'=>$content2);
    die (json_encode2($answer));
    
}


if (isset($_REQUEST['KillSession'])){
    session_unset();
    echo "<br /><br />SESSION CLEAR<br /><br />";
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
    <link href="../resources/style/nossl.css" type="text/css" rel="stylesheet" />
    <script src="../resources/javascript/jquery110.js"></script>
    <script src="../resources/javascript/nossl_standard.min.js"></script>


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
            var tester = '{"Testmsg":"This is a test"}';
            $.ajax({
            type: "POST",
            url: "./demo.php",
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
        var tester = '{"Testmsg":"Another test."}';
        //console.log('AES Key js: '+nossl.getStuff());    
        $.post( "./demo.php", {nossl_ajaxtest:tester}, function( msg ) {
            var got =  $.parseJSON(msg);
            //got.response = nossl.decrypt(got.response);
            $( "#response3" ).html('<span style="color:green;font-weight:bold">'+got.response+'</span>');
        });
    });


	});


   
	
	</script>
	<title>NoSSL AJAX demo</title>
</head>
<body>
    <div id="content">	
				<h1>NoSSL Demo Prerelease 0.9beta</h1>
				<strong style="color:red">This demo is a prerelease and may not be used in productive systems!</strong>
				<p style="font-size: small;">The software NoSSL is licensed under GPL for private/non-commercial use. For commercial use, please
                inquire about the prices on our website <a href="http://www.nossl.net">www.nossl.net</a></p>
				<p style="font-size: small;">This demo shows you how you can send data from a form or via AJAX-requests with NoSSL encryption.
                When you click on "Send data" or on the Test-buttons, the data are locally encrypted (in the browser)
                and then transferred to the server. The server decrypts the message. For demonstration purposes, you 
                receive an answer back. Activate your Firebug to also check, what is going through the network.<br /><br />
                If you get any error message, please contact us: info@smartinmedia.com.</p>
	
        <div class="formdiv">
			<form action="demo.php" class="testclass andanother" onsubmit="return CheckInput();" method="post">
			      <table>
    			      <tr>
                      <td>User name:</td><td><input type="text" name="username" /></td>
                      </tr>
                      <tr>
                      <td>Password:</td><td><input type="password" name="password" /></td>
                      </tr>
    			      <tr>
                      <td>Textarea:</td><td><textarea name="textarea_field2">Please add your msg to encrypt!</textarea></td>
			          </tr>   
                  </table> 
                   <br />

			     <input type="submit" name="test" value="Send data!"/>
            </form>
        </div>
        <div class="formdiv">
            <form action="demo.php" class="testclass andanother" onsubmit="return killSession();" method="post">
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
  
    
     <?php
        //echo $nossl_echo_this_into_body;
    
    
       if (isset($_REQUEST['password'])){
        echo "<h2>The browser sent (encrypted):</h2>";
        if (isset($_REQUEST['nossl_encrypted_form_values'])) echo $_REQUEST['nossl_encrypted_form_values'];
        else echo "<br />No encrypted data";
        
        echo "<h2>The server decrypted this to:</h2>";
        echo "<p>Username: ".$_REQUEST['username']."</p>";
        echo "<p>Password: ".$_REQUEST['password']."</p>";
        echo "<p>Your comment: ".$_REQUEST['textarea_field2']."</p>";
        //nossl_echo ("<br /><br /><br /><strong>This is a test to send encrypted stuff.</strong><br /><br />");
    
    
    }
    
    
    
     ?>
      <br /><br /><br /><br />
      <strong style="font-size: small;">NoSSL was invented and developed by <a href="http://www.smartinmedia.com">Smart In Media</a> &copy;2014 - all rights reserved -</strong>
  
  </div>

</body>
</html>
