<?php
error_reporting(-1);
require_once('./nossl/nossl_start.php');

//echo ($nossl->encrypt('<h1>This is a big Test</h1>'));


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
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

    
    <link href="./nossl/style/nossl.css" type="text/css" rel="stylesheet" />
  
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
    
    <script src="./nossl/javascript/jquery.js"></script>

    <script src="./nossl/javascript/nossl_start.min.js"></script>
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
       



	});
	
	</script>
	<title>NoSSL demo</title>
</head>
<body>
	  <div id="content">
				<h1>NoSSL Demo</h1>
				<!-- <h2><small>Example by <a href="http://www.smartinmedia.com">Smart In Media</a></small></h2> -->
	
          <div class="formdiv">
			<form action="<?php echo basename(__FILE__); ?>" class="testclass andanother" onsubmit="return CheckInput();" method="post">
			      User name: <input type="text" name="username" /> <br />
			      Password: <input type="password" name="password" /> <br />
			      Your comment: <textarea name="textarea_field2">Ein Test</textarea><br />
			       File: <input type="file" name="filename"/> 
                   <br />

			     <input type="submit" name="test" value="Send data!"/>
            </form>
         </div>   
         
         
         <div class="formdiv">   
            <form action="<?php echo basename(__FILE__); ?>" class="testclass andanother" onsubmit="return killSession();" method="post">
			      Kill session
			      <input type="hidden" name="KillSession"/>
			     <input type="submit" name="test2" value="Kill session!"/>
            </form>
         </div>
     
     <?php
    //echo $nossl_echo_this_into_body;


   if (isset($_REQUEST['password'])){
    echo "<p><h2>You sent:</h2> </p>";
    echo "<p>Username: ".$_REQUEST['username']."</p>";
    echo "<p>Password: ".$_REQUEST['password']."</p>";
    echo "<p>Your comment: ".$_REQUEST['textarea_field2']."</p>";
    echo "<p>The request array:</p>";
    print_r ($_REQUEST);
    
    //nossl_echo ("<br /><br /><br /><strong>This is a test to send encrypted stuff.</strong><br /><br />");
}
 ?>
     
     
     
     </div>       

<?php 
    //echo $nossl_echo_this_into_body;
    

 ?>

</body>
</html>
