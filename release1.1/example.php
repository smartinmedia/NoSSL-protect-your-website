<?php
error_reporting(-1);
require_once('./nossl/nossl_start.php');

//echo ($nossl->encrypt('<h1>This is a big Test</h1>'));

if (isset($_REQUEST['nossl_ajaxtest'])){
    $content1 = json_decode($_REQUEST['nossl_ajaxtest'], true);
    $content2 = 'You sent this: '.utf8_decode($content1['testmessage']); 
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
    <link href="./nossl/style/nossl.css" type="text/css" rel="stylesheet" />
    <script src="./nossl/javascript/jquery.js"></script>

	<script src="./nossl/javascript/nossl_start.min.js"></script>

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
            var tester = '{"testmessage":"This is a message..."}';
            $.ajax({
            type: "POST",
            url: "./<?php echo basename(__FILE__); ?>",
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
        var tester = '{"testmessage":"...and another message with Jquery post"}';
        //console.log('AES Key js: '+nossl.getStuff());    
        $.post( "./<?php echo basename(__FILE__); ?>", {nossl_ajaxtest:tester}, function( msg ) {
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
				<div id="head"><img src="./nossl/images/nossl-logo.gif" width="250" alt="" /><span style="position:relative; font-size:40px; top:-12px;">&nbsp;Demo</span></div>
                &copy; by <a href="http://www.smartinmedia.com">Smart In Media</a>
	           <p>To learn how to EASILY implement NoSSL, just go to <a href="http://www.nossl.net">www.NoSSL.net</a></p>

<?php 	

if (isset($_REQUEST['password'])){
    echo "<br />You sent this encrypted message: <br />";
    echo '<span style="font-size:10px;">'.$_REQUEST['nossl_encrypted_form_values'].'</span>';
    echo "<br /><br /><div style='border: 1px solid blue;'><span style='color:#0000A3'>After decryption by the server, this reads...</span>";
    echo "<br /><strong>Username: ".$_REQUEST['username']." Password: ".$_REQUEST['password']."<br />Textarea: ".$_REQUEST['textarea_field2']."</strong></div>";
    
}

 ?>
	
	
	
	
	
	
        <div class="formdiv">
			<form action="<?php echo basename(__FILE__); ?>" class="testclass andanother" onsubmit="return CheckInput();" method="post">
			      User name: <input type="text" name="username" /><br />
			      Password: <input type="password" name="password" /><br />
			      Textarea: <textarea name="textarea_field2">Please enter something...</textarea><br />
			       <br />

			     <input type="submit" name="test" value="Send data!"/>
            </form>
        </div>    
        <!-- 
        <div class="formdiv">    
            <form action="example.php" class="testclass andanother" onsubmit="return killSession();" method="post">
			      Kill session
			      <input type="hidden" name="KillSession"/>
			     <input type="submit" name="test2" value="Kill session!"/>
            </form>
        </div> 
         -->   
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
