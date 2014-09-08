/*
########################################################################################

## NoSSL V1.1 - Encryption between browser and server

########################################################################################

## Copyright (C) 2013 - 2014 Smart In Media GmbH & Co. KG

##

## http://www.nossl.net

##

########################################################################################



THIS PROGRAM IS LICENSED FOR PRIVATE USE UNDER THE GPL LICENSE



FOR COMMERCIAL USE, PLEASE INQUIRE THROUGH www.nossl.net



########################################################################################
*/


function NoSSL() //The ways-variable is the switch if NoSSL works 1way or 2ways (only from client to server or also from server to client)
{
    var debugging = false; //Switch to false to switch off console.logs
    var that = this; //This is so public functions can accessed from within private ones
    var timeouthandler; //The handler variable for settimeout for the remaining lease time
    
    auto_encryption = 1; //If set to 1, all forms are automatically encrypted. If set to 0, the programmer has to perform enryption and decrcyption with nossl.encrcypt() and nossl.decrypt() himself
    this.nossl_path = ''; //This will store the path to the NoSSL-Directory. This is important for AJAX and image-requests used in the documents
    
    this.cleanSessionStorage = function(){
        //This function removes all session Storage variables of NoSSL
        //debug('In cleanSessionStorage');
        sessionStorage.removeItem('nossl_rsa_key'); sessionStorage.removeItem('nossl_aes_key'); sessionStorage.removeItem('nossl_server_time'); sessionStorage.removeItem('nossl_sessionID'); sessionStorage.removeItem('nossl_babel');sessionStorage.removeItem('nossl_directions'); sessionStorage.removeItem('nossl_remaining_lease_time');
        debug('Session ID after clean: '+sessionStorage.getItem('nossl_sessionID'));
    }
   
   
   this.getStuff = function(){
       return getAESKey();         
   }
   
    function isSessionSet(){
        //debug('In isSessionSet');
        if (typeof sessionStorage.getItem('nossl_sessionID')==="undefined" || sessionStorage.getItem('nossl_sessionID')=='' || sessionStorage.getItem('nossl_sessionID')===null) {
            //debug('Typeof: '+(typeof (sessionStorage.getItem('nossl_sessionID')))+'Session is: '+sessionStorage.getItem('nossl_sessionID')+' session Method: '+sessionStorage.getItem('nossl_storeMethod'));
            return false;
            }
        debug('Session is set: '+sessionStorage.getItem('nossl_sessionID'));
        return true;
                
    }
    
    function showHiddenSubmitButton(){//This function displays the otherwise hidden submit button, once the server settings are gotten. Thus, it is prevented that the user can submit anything without JavaScript ON or the Serverkey not received.
        if ($('form') && auto_encryption == 1){//Is there any form in the DOM?

                if ($(".nossl_remove")[0]){
                    $('.nossl_remove input[type="submit"]').show();
                }
                else if ($(".nossl_protect_form")[0]){//Is there any form-protect?
                    $('.nossl_protect_form').show();
                    $('.nossl_protect_form').closest("form").append('<br/><div>Protected by <a href="http://www.nossl.net" target="_blank"><img border="0" alt="NoSSL" style="" src="'+getServerPath()+'images/nossl-logo-60.png"/></a></div>');
                    $('.nossl_protect_form').closest("form").addClass('nossl_remove');
                    //$('form').addClass('nossl_remove');
                }
                else{
                    $('form').addClass('nossl_remove');
                    $('form input[type="submit"]').show();
                    $("form").append('<br/><div>Protected by <a href="http://www.nossl.net" target="_blank"><img border="0" alt="NoSSL" style="" src="'+getServerPath()+'images/nossl-logo-60.png"/></a></div>');

                }

            }
            
    }                                                                  
    
    
    this.initializeClient = function(){
            debug('In initializeClient');
            storeServerPath(parseServerPath());//Store the Server Path, e. g. http://www.example.com/cunity/nossl/
            //Look for stuff that was sent encrypted from the server!
            
            if (isSessionSet() && sessionStorage.getItem('nossl_storeMethod')!=='standard' ) {//If browser does not support sessionStorage, then we have to check, if the session is still valid
                debug('Browser does not support session Storage. Therefore we are handshaking again');
                if($('#nossl_serversettings')[0]){//If there are server settings in the HTML document, we can grab them and do not need to do handshake 1
                        
                        var serverSettings =  $('#nossl_serversettings').text();
                        serverSettings =  serverSettings.replace(/ +|\\r+|\\n+|\\t+/g, '');
                        var msg = jQuery.parseJSON(serverSettings);
                        handleAjaxCallback(msg); //We are giving the HTML stuff to the handleAjaxCallback so it is processed as if we had an AJAX handshake #1
                }                        
                else handshake(1);//Else we get the server settings again
            }
            
            if (!isSessionSet() || getNoSSLDirections()==1 || getNoSSLDirections()===false) {//If we dont have a session, do a handshake unless we get the server settings from the HTML - THIS HAPPENS ALWAYS IF encdirections IS FALSE, because 1direction never creates a sessionID (it is in handshake step 2)
                
                if($('#nossl_serversettings')[0]){//If in the HTML, there is  the class ".nossl_serversettings" present, then we can spare handshake #1, but just use this and put it into handleAjaxCallback, even if it wasnt from AJAX, but just in the HTML page :)
                        debug('In nossl_serversettings in HTML, 2nd possibility');
                        var serverSettings =  $('#nossl_serversettings').text();
                        serverSettings =  serverSettings.replace(/ +|\\r+|\\n+|\\t+/g, '');
                        var msg = jQuery.parseJSON(serverSettings);
                        handleAjaxCallback(msg); //We are giving the HTML stuff to the handleAjaxCallback so it is processed as if we had an AJAX handshake #1
                                    
                }
                else handshake(1);

            }
    }
    
    /*Function, which harvests and stores the server path. Important for AJAX requests and image loading from server
    */
    function parseServerPath(){
        //Parses the Html document for the nossl_start.js script. When found, it can read out the path
        var scripts = document.getElementsByTagName("script");
        for (var i=0;i<scripts.length;i++) {
    // if (scripts[i].src) console.log(i,scripts[i].src)
            if (scripts[i].src.toLowerCase().indexOf("nossl_start.js")!==-1){//We found //..../nossl_start.js, now extract the path!
                return (scripts[i].src.replace("javascript/nossl_start.js", "") );//Will deliver e. g. http://www.example.com/cunity/nossl/ as the NoSSL path
                    
            }
            else if (scripts[i].src.toLowerCase().indexOf("nossl_start.min.js")!==-1){//We found //..../nossl_start.js, now extract the path!
                return (scripts[i].src.replace("javascript/nossl_start.min.js", "") );//Will deliver e. g. http://www.example.com/cunity/nossl/ as the NoSSL path

            } 
            
        }
        return false;
  //throw new Error();    
    }
    
    
    //In case of encdirectionsectional NoSSL, we need to do 2 handshakes: 
    //First, the client requests the public key and second, the client sends the encrypted AES session key
    function handshake(handshake_no){
        removeAESKey(); // Every handshake resets the AESKey - new game!
        if (handshake_no==1 || typeof handshake_no ==="undefined"){
            //Just request the public key and the server time. Also we request, if there is a session variable and if yes, we'll get a hashed version of it. This is to 
            //check, if the same session is still valid for browsers without sessionStorage-support
            var new_client_id_needed = 0;
            if (getRemainingLeaseTime()==-1 || getClientID()===false || getClientID()=='0'){//If we need a new client ID
                new_client_id_needed = 1;
                //Now we have to remove the possibility again to submit the forms from the forms that have been protected before while AJAX is working
                if ($(".nossl_remove")[0]){
                    $('.nossl_remove input[type="submit"]').hide();
                }
                debug(' My new_client_id_needed: '+new_client_id_needed);                
            }
            else{
            }
            
            var ajax_transfer = {'handshake_msg':'getServerSettings', 'need_client_id': new_client_id_needed};
            
            ajax_transfer = JSON.stringify(ajax_transfer);


            $.ajax({
            type: "POST",
            url: (getServerPath()+"nossl_start.php"),
            data:{nossl_json_data1: ajax_transfer},
            //{nossl_json_data1: '{data:'+handshake_msg+'}'},
            //, need_client_id:'+new_client_id_needed+
            async: false,
            dataType: "json",
            beforeSend: function() {},
            success: function(msg) {
                if (msg.status === false){
                    debug('Ajax Function error');
                }
                else {    //If everything worked OK
                    handleAjaxCallback(msg); //We have to do this (cant return this) as AJAX is asynchronous
                }}, 
            error: function() {
                debug('An error ocurred (AJAX ERROR)!');
                }
            });
       }
       else { //handshake_no=2 --> Send the encrypted AES-session-key
            var handshake_msg = {'package':that.encrypt('handshake')};
            handshake_msg = JSON.stringify(handshake_msg);
            $.ajax({
            type: "POST",
            url: (getServerPath()+"nossl_start.php"),
            data: {nossl_json_data2: handshake_msg},
            async: false,
            dataType: "json",
            beforeSend: function() {},
            success: function(msg) {
                if (msg.status === false){
                    debug('Ajax Function error');
                }
                else {    //If everything worked OK
                    handleAjaxCallback(msg); //We have to do this (cant return this) as AJAX is asynchronous
                }},
            error: function() {
                debug('An error ocurred (AJAX ERROR)!');
                }
            });            
       }
    }
    
    
    function handleAjaxCallback(msg) {
        debug('msg.nossl_task: '+msg.nossl_task);
        if (msg.nossl_task == '1') {//First handshake to get the public key
            clearTimeout(timeouthandler);//We clear the timeout for the remaining lease time of the key as there will be a new one
            auto_encryption = parseInt(msg.nossl_auto_encryption);
            that.cleanSessionStorage(); //Clear all session elements and start over
            debug('that.autoenc is: '+ auto_encryption);
            if (msg.nossl_babel_switch == '1'){
                storeBabel(Base64.decode(msg.nossl_babel_content));
            }
            if (getRSAKey()!==false){
                if(getRSAKey()!==msg.nossl_rsa_publickey && msg.nossl_clientid == 'zero'){//If the new RSA-key is a new one, then we have to again do a new handshake asking for a new client ID!
                    storeClientID('0'); //With this we force that the ClientID is "0" (session Storage only takes strings)
                    handshake(1); //Repeat the handshake to this time get a new ClientID with the new key.                                             
                }
            }
            storeRSAKey(msg.nossl_rsa_publickey);
            storeServerTime(msg.nossl_servertime);
            storeVersion(msg.nossl_version);
            storeRemainingLeaseTime(msg.nossl_remaining_lease_time);
            storeNoSSLDirections(msg.nossl_directions);
            if (msg.nossl_clientid != 'zero') storeClientID(msg.nossl_clientid);//If the client really needs a new clientID, then this will be not "zero"
            debug('ClientID is: '+msg.nossl_clientid);
            showHiddenSubmitButton(); //Now release the Submit Button as we have everything ready...
            
            debug('I harvested this key: '+msg.nossl_rsa_publickey+' directions: '+msg.nossl_directions+' RemainingLeaseTime: '+msg.nossl_remaining_lease_time);
            if (msg.nossl_directions == 2){//If we work with bidirectional encryption, we eventually need handshake number 2, else not
                if (isSessionSet && sessionStorage.getItem('nossl_storeMethod')!=='standard'){//If this is a browser without session support
                    if (SHA1(msg.nossl_salt+getSessionID())===msg.nossl_sessionID_hashed) return true; //We still have the correct and same session as the server, thus dont do anything; else do handshake 2
                    else alert('Session ID does not match. Error in NoSSL!');
                }
                handshake(2);//After handshake 1 comes handshake 2
            }
        }
        else if (msg.nossl_task == '2') {//Second handshake, just to confirm the server has the AESKey and receive the NoSSL - SessionID
            //debug('Received stuff: RSAkey: '+msg.publickey+' SessionID: '+msg.sessionID+' serverTime: '+msg.servertime);
            storeRSAKey(msg.nossl_rsa_publickey);
            storeSessionID(that.decrypt(msg.nossl_sessionID));//This can only be done in step 2 as in step2 we get the encrypted session number
            storeServerTime(msg.nossl_servertime);
            storeVersion(msg.nossl_version);
            storeRemainingLeaseTime(msg.nossl_remaining_lease_time);
            storeNoSSLDirections(msg.nossl_directions);
            
        }
    }


    function getRandomInt(min, max) {
         return Math.floor(Math.random() * (max - min)) + min;
    }
    
    function storeServerPath(serverpath){
        sessionStorage.setItem('nossl_serverpath', serverpath);
    }

    function getServerPath(){
        if (sessionStorage.getItem('nossl_serverpath')===null) return false;
        return sessionStorage.getItem('nossl_serverpath');
    }
    
    function storeClientID(clientid){
        sessionStorage.setItem('nossl_clientid', clientid);
    }

    function getClientID(){
        if (sessionStorage.getItem('nossl_clientid')===null) return false;
        return sessionStorage.getItem('nossl_clientid');
    }

    
    function storeRSAKey(key){
    //Store the armored RSA Key: with @NoSSL_RSAKey...
        key =  key.replace(/ +|\\r+|\\n+|\\t+/g, ''); //Remove all line breaks that may have ocurred
        sessionStorage.setItem('nossl_rsa_key', key);
    }

    
    function getRSAKey(){
        //Retrieve the RSA Key
        if (sessionStorage.getItem('nossl_rsa_key')===null) return false;
        return sessionStorage.getItem('nossl_rsa_key');
    }
    
    function storeServerTime(servertime){
        //Store the server time as int
        sessionStorage.setItem('nossl_server_time', servertime);
    }
    
    function getServerTime(){
        //Store the server time as int
        if (sessionStorage.getItem('nossl_server_time')===null) return false;
        return sessionStorage.getItem('nossl_server_time');
    }
    
    function timeoutdone(){//If the remaining lease time is used up, we have to do this
        storeRemainingLeaseTime(-1);//We put this to "-1", because in the handshake, we check, if remaining lease time is left. Else we need a new client ID, when the lease time is up
        debug('Storing: '+getRemainingLeaseTime());
        handshake();        
    }
    
    function storeRemainingLeaseTime(leasetime){
        //Store the lease time as int
        sessionStorage.setItem('nossl_remaining_lease_time', parseInt(leasetime));
        var interval =  parseInt(leasetime)*1000+5000;
        timeouthandler = setTimeout (timeoutdone, interval); //5 seconds after the remaining lease time has run out, we do a handshake again to harvest a new server key. 
    }
    
    function getRemainingLeaseTime(){
        //Store the server time as int
        if (sessionStorage.getItem('nossl_remaining_lease_time')===null) return false;
        return sessionStorage.getItem('nossl_remaining_lease_time');
    }
    
    function storeBabel(babel){
        //the array babel
        sessionStorage.setItem('nossl_babel', babel);
    }

    function getBabel(){
        if (sessionStorage.getItem('nossl_babel')===null) return false;
        return JSON.parse(sessionStorage.getItem('nossl_babel'));
    }
    
    function storeNoSSLDirections(directions){
        if (directions==1 || directions=="1") {
            directions = 1; //Change to int, if it was text   
        }
        else {
            directions = 2;    
        }
        sessionStorage.setItem('nossl_directions', directions);
        
    }

    function getNoSSLDirections(){
        if (sessionStorage.getItem('nossl_directions')===null) return false;
        return sessionStorage.getItem('nossl_directions');
    }
    
    
    function storeVersion(version){
        //Store the server time as int
        sessionStorage.setItem('nossl_version', version);
    }

    function getVersion(){
        //Store the server time as int
        if (sessionStorage.getItem('nossl_version')===null) return false;
        return sessionStorage.getItem('nossl_version');
    }
    
     function storeSessionID(sessionID){
        //Store the server time as int
        if(typeof sessionID==='undefined') {debug('The session ID which I received is undefined'); return false;}
        sessionStorage.setItem('nossl_sessionID', sessionID);
        debug('In Store Session, session is: '+sessionStorage.getItem('nossl_sessionID'));
    }

    function getSessionID(){
        //Store the server time as int
        if (sessionStorage.getItem('nossl_sessionID')===null) return false;
        return sessionStorage.getItem('nossl_sessionID');
    }
    
    
    function storeAESKey(key){
        //Store the armored AES Key 
        sessionStorage.setItem('nossl_aes_key', key); 
    }

    function getAESKey(){
        if (sessionStorage.getItem('nossl_aes_key')===null) return false;
        return sessionStorage.getItem('nossl_aes_key');
    }
    
    function removeAESKey(){
        if (sessionStorage.getItem('nossl_aes_key')===null) return false;
        sessionStorage.removeItem('nossl_aes_key');
    }

    function createAESKey(){
        var rnd = new Uint8Array(32);//Create 32byte-container (256 bit)
        window.crypto.getRandomValues(rnd);//Create a random 32byte/256bit-key
        //Now convert these bytes to a Base64-string, so we can return and store it
        var key = armorAESKey(rnd);
        return key;
    }
    
    function existServerMsgID(servermsgid){
        if (sessionStorage.getItem('servermsgid')===null){
            sessionStorage.setItem('servermsgid', '{}');
        }
        var obj = JSON.parse(sessionStorage.getItem('servermsgid')); // Convert to an object
        if (typeof obj[servermsgid] === 'undefined') return false;
        else return true;        
    } 
    
    function storeServerMsgID(servermsgid){
        if (sessionStorage.getItem('servermsgid')===null){
            sessionStorage.setItem('servermsgid', '{}');
        }
        var obj = JSON.parse(sessionStorage.getItem('servermsgid')); // Convert to an object
        obj[servermsgid] = 1;
        sessionStorage.setItem('servermsgid', JSON.stringify(obj));
    }
    
    
    function getNewMessageID(){ //This produces a random MessageID, which has to be unique
        var msgID = new Date().getTime(); 
        msgID = (msgID+getRandomInt(1, 9999999)); 
        msgID = 'message_' + msgID.toString();
        
        return msgID;
    }

    function unarmorPTR(text){
        var regexp=/@NoSSL_PTR_begin@([\s\S]*)@NoSSL_PTR_end@/g;
        var temp = regexp.exec(text);
        return temp[1];
    }
    function unarmorETA(text){
        var regexp=/@NoSSL_ETA_begin@([\s\S]*)@NoSSL_ETA_end@/g;
        var temp = regexp.exec(text);
        return temp[1];
    }
    function unarmorMessage(text){
        var regexp=/@NoSSL_Message_begin@([\s\S]*)@NoSSL_Message_end@/g;
        var temp = regexp.exec(text);
        return temp[1];
    }
   /* 
    function unarmorNoSSLDirections(text){
        var regexp=/@NoSSL_Directions_begin@([\s\S]*)@NoSSL_Directions_end@/g;
        var temp = regexp.exec(text);
        return temp[1];
    }
    
    function unarmorNoSSLRemainingLeaseTime(text){
        var regexp=/@NoSSL_RemainingLeaseTime_begin@([\s\S]*)@NoSSL_RemainingLeaseTime_end@/g;
        var temp = regexp.exec(text);
        return temp[1];
    }
    */
    
    function unarmorPackage(text){
        var regexp=/@NoSSL_Package_begin@([\s\S]*)@NoSSL_Package_end@/g;
        var temp = regexp.exec(text);
        text = temp[1];
        var content = new Array();
        content['Message'] = unarmorMessage(text);
        content['PTR'] = unarmorPTR(text);
        content['ETA'] = unarmorETA(text);
        return content;
     }        
    
    
    
    /*
     * The standard NoSSL encryption function, which produces a message. Also uses a test string (PT) to check, if the encryption went well
     * 
     */
    this.encrypt = function(plaintext){
    /*
    * PT: plain test - this is a random SHA1-string, which we use as a plaintext for encryption (so the decryptor know by testing, if the whole decryption works 
    * correctly before extracting everything that was send)
    * ETR: RSA-encrypted PT
    * ETA: AES-encrypted PT
    * 
    */
        var message_type = 'AES'; //Default AES, will be changed to RSA, if RSA-encryption of AES-key is added
        var rsa_string='', etr_string='', msg_key_string=''; babel_array=['', '', '']; //These will be later used to build the package with or without RSA
        //Now get a random value (plain test = PT)that we will encrypt in plaintext with the message, so we can check later, if the decryption was correct (test values)       
        
        var pt = (SHA1(createAESKey())).substr(4,10);
        if (getAESKey()===false || getNoSSLDirections()==1) {//If there is no AESKey or if it's one-way NoSSL, let's make a new AESKey!
            var tempAESKey = createAESKey();
            storeAESKey(tempAESKey);
            debug('Making new AES Key: '+tempAESKey);
        }
        var AESKey = getAESKey();
        
        var eta = AESEncrypt(pt, AESKey);
        var clientid = AESEncrypt(getClientID(), AESKey); 
        //Make a uniqe message ID based on time and randomness..
        var msgID = AESEncrypt(getNewMessageID(), AESKey);
                
        var temp_babel = getBabel();
        if (temp_babel!==false){
            for (var i=0; i<3; i++){
                var rand = Math.floor(Math.random()*( (temp_babel.length)-2 ));
                babel_array[i] = temp_babel[rand];//Get 2 random words from babel and seed
            }
        }
        
        //alert(temp_babel[7]);
            
        //alert(babel_string);
        
//        debug ('plaintest: '+pt);
        //{'SessionID': #SessionID#, 'Timestamp': #UnixTimeStamp in Seconds!#, 'MsgID': #RunningNo_10digitHash#, 'Message': #MessageText#}
        var messagetext = {'SessionID':getSessionID(), 'Timestamp':time(), 'MsgID': getNewMessageID(), 'Message': plaintext};
        var ciphertext = AESEncrypt(JSON.stringify(messagetext), (AESKey));
        
        if (!isSessionSet() || !encdirections){ //If a Session was not initiated or we use onedirectional encryption, we use the full RSA/AES Encryption in the message
            message_type='RSA';
            var RSAKey = getRSAKey();
            //debug('Time before RSA: '+time());
            var etr = this.RSAEncrypt(pt, (RSAKey));
            var enc_AESKey = this.RSAEncrypt(AESKey, (RSAKey));
            //debug('Time after RSA: '+time());
            rsa_string = RSAKey; 
            etr_string = '@NoSSL_ETR_begin@'+etr+'@NoSSL_ETR_end@';
            msg_key_string = '@NoSSL_MessageKey_begin@'+enc_AESKey+'@NoSSL_MessageKey_end@';
       }    
        
        var armored_message =   '@NoSSL_Package_begin@---'
        /*Version*/             +'@NoSSL_Version_begin@'+getVersion()+'@NoSSL_Version_end@'
                                +babel_array[0]
        /*MsgType*/             +'@NoSSL_MsgType_begin@'+message_type+'@NoSSL_MsgType_end@'
        /*RSAKEY */             +rsa_string //Just sending this again, so the server will no, what we used
                                +'@NoSSL_PTR_begin@'+pt+'@NoSSL_PTR_end@' //Plain Test RSA, e. g. 10 random characters begins / ends here
                                +etr_string //Encrypted Test RSA, the encrypted 10 random characters to check, if decryption was fine
                                +babel_array[1]
                                +'@NoSSL_ETA_begin@'+eta+'@NoSSL_ETA_end@'//Encrypted Test AES, if the decryption on AES works
                                +msg_key_string
                                +'@NoSSL_ClientID_begin@'+clientid+'@NoSSL_ClientID_end@'
                                +'@NoSSL_MessageID_begin@'+msgID+'@NoSSL_MessageID_end@'
                                +'@NoSSL_Message_begin@'+ciphertext+'@NoSSL_Message_end@'
                                +'---@NoSSL_Package_end@'
                                +babel_array[2];
        return armored_message;
    }
    
    
    
    this.decrypt = function(ciphertext){
        if (!encdirections) {
            debug('You are JavaScript-decrypting something in the 1way-mode. That doesnt make sense!');
            return false;
        }
        //This function is the easiest to use. Just decrypt the entire NoSSL-package
         //First check, if there is anything usable in it anyway
         if (ciphertext.indexOf('@NoSSL_Package_begin@') === -1){
             return false;
         }
         if (!getAESKey()) {
            debug('No AES Key set'); 
            handshake(); 
            return false;
         } //If there is no session, we have to first perform the handshake 
         var content = unarmorPackage(ciphertext); //Get the message unarmored
         if (AESDecrypt(content['ETA'], getAESKey())!==content['PTR']){
             debug('Decryption did not work -> AES failure in NoSSL!');
             return false;
         }
        
         var dec_message = AESDecrypt(content['Message'], getAESKey());
         var dec_object = JSON.parse(dec_message); //Convert the JSON string to an object
         if (existServerMsgID(dec_object['MsgID']) && dec_object['Allow_resend']=='0') {debug('Security problem: Message ID was already used before!: '+dec_object['MsgID']); return false;} //If the message has been sent before and if this is not allowed according to the config.php
         storeServerTime(dec_object['Timestamp']);
         return dec_object['Message'];
    }
    
    
    /*
     * This is used to convert the AES random key into something transferable and human readable 
     */
    
    function armorAESKey(key){ //accepts a byte array
        return '@NoSSL_AESKey_begin@'+Base64.encode(ab2str(key))+'@NoSSL_AESKey_end@';    
    }
    
    function unarmorAESKey(key){
        var regexp=/@NoSSL_AESKey_begin@(([\s\S]*))@NoSSL_AESKey_end@/g;
        //debug('Again, the key: '+key);
        var temp = regexp.exec(key);
        //debug('after regex: '+temp[1]);
        return str2ab(Base64.decode(temp[1]));
    }
    function unarmorServerTime(servertext){
        var regexp=/@NoSSL_ServerTime_begin@([\s\S]*)@NoSSL_ServerTime_end@/g;
        var  temp = regexp.exec(servertext);
        return parseInt(temp[1]); //Returns servertime as Int from year 01.01.1970 in seconds
    }
    
    function unarmorVersion(servertext){
        var regexp=/@NoSSL_Version_begin@([\s\S]*)@NoSSL_Version_end@/g;
        var  temp = regexp.exec(servertext);
        return parseInt(temp[1]); //Returns servertime as Int from year 01.01.1970 in seconds
    }


    

    
    function unarmorRSAKey(key){
        debug('RSA key: '+key);
        var regexp=/@NoSSL_RSAKey_begin@([\s\S]*)@NoSSL_RSAKey_end@/g;
        var temp = regexp.exec(key);
        return temp[1];
    }    
    
    function ab2str(buf) {//Converts binary Array to string
        return String.fromCharCode.apply(null, new Uint8Array(buf));
    }

    function str2ab(str) {//Converts string to binary Array
        //var buf = new ArrayBuffer(str.length); // 2 bytes for each char
        var bufView = new Uint8Array(str.length);
        for (var i=0, strLen=str.length; i<strLen; i++) {
            bufView[i] = str.charCodeAt(i);
        }
        return bufView;
    }
    
    /*
     * nossl.encrypt() // .decrypt()
     * These are standard functions that take an AES key, encrypt a message, then encrypt the AES key with the RSA key and put the whole thing into a message
     */
     
     
    function time () {
        return Math.floor(new Date().getTime()/1000);                                           
    }
    
    function debug(string){
        if (debugging == true ){
            if ((document.all && !document.querySelector))
                return false;
            else console.log(string);                
        }
        
   } 
    /*
     * With this, the encrypted texts are harvested from the HTML page.
     */
    this.parseAndDecryptHTML = function(){
        //This function harvests all text, which is between the NoSSL delimiters
        //@NoSSL_start@ and @NoSSL_end@ and returns an an array with the text
        if (!encdirections) {
            debug('You are JavaScript-parse-and-decryptingHTML in the 1way-mode. That doesnt make sense!');
            return false;
        }
        var texts = new Array();//Will have the result
        var regexp=/@NoSSL_Package_begin@---[\n\r\s\t]*@NoSSL_Version_begin@([\s\S]*)---@NoSSL_Package_end@/g;
        var temp;
        
        var htmltext = $("html").html();
        while ((temp = regexp.exec(htmltext)) !== null){
        
        //if ((temp = regexp.exec(htmltext))=== null) debug('temp is null');
        
        //else debug('temp0: '+temp[0]);
        //    debug('HERE!');    
            texts.push (temp[0]);
        }
        debug('Number of texts: '+texts.length);
        for (var i=0; i<texts.length; i++){
            debug('Texts: '+texts[i]);
            dec = this.decrypt(texts[i]);
            htmltext = htmltext.replace(texts[i], dec); //Replace the encrypted with the decrypted    
        }
        $("html").html(htmltext);
    }
    
    
    this.RSAEncrypt = function(plaintext, publickey){
        //Public key must be in Hex-format!
        publickey = unarmorRSAKey(publickey);
        var rsakey = new RSAKey();
       	rsakey.setPublic(publickey, "10001");
		var encryptedtext = rsakey.encrypt(plaintext);
		return encryptedtext;
	}



    AESEncrypt = function(plaintext, key){
        //Convert the human-readable key to byte-stuff
        key = unarmorAESKey(key);
        //debug('AESKey new: '+key);
        
        //var time1 = (new Date().getTime());
        return ciphertext = Aes.Ctr.encrypt(plaintext, key, 256);
        //var time2 = (new Date().getTime());
        //var timediff = time2 - time1;
        //debug('TimeDiff: '+timediff);
        //$('#temp').html("key: "+pass+" cipher: "+ciphertext+"<br /><br />");
        
    }
    
    function AESDecrypt(ciphertext, key){
        key = unarmorAESKey(key);                   
        debug('Key length:'+key.length+' cipher: '+ciphertext);
        return Aes.Ctr.decrypt(ciphertext, key, 256);
    }                                      

    this.formEncrypt = function(formObj){
        debug('In form Encrypt');
        if ($('.nossl_encrypted_submit_now').length > 0) $('.nossl_encrypted_submit_directly').remove(); //Get rid of old elements that we dont need
        var formObjAttributes =  formObj.nossl_getAttributes();
        
        //var formObjAttributes =  formObj.mapAttributes();
        var field_values = formObj.serialize();
        debug('Field values: '+field_values);
        var formID = Math.floor((Math.random()*1000000)+1); //Create an ID to individually call that later again...      
        if(formObj.find('input:file').length>0){//If there is a file-upload field, we can notify the user that this is not safe as it will be not encrypted
            //alert('NoSSL does not support forms containing file-elements as file uploads will not be encrypted!');
        }
         
        /*
        * NOTE: CLONING THE FILE FIELD DOES NOT WORK WITH JAVASCRIPT
        
        */
        
        var copiedForm = '<div id="test7"><form style="display:none;" id="'+formID+'" '; //This will be an "artificial" form that contains all data from the original form, only encrypted. We are doing this so that the user will still see the correct data he entered in the form in an unencrypted way. Thus, the user can later still resubmit the form, if something went wrong
        //var submit_field = (formObj.find(':submit')).outerHTML() ? (formObj.find(':submit')).outerHTML() : '';
        for (var key in formObjAttributes){
            debug('attribute key: '+key);
            if (key.toLowerCase() !='onsubmit' && key.toLowerCase()!='id' && formObjAttributes[key].toLowerCase()!='null' && formObjAttributes[key].toLowerCase()!=''){//We'll remove the onsubmit stuff as we dont want to repeat this; also we dont want to get in conflict with any ids
                
                if (document.all && !document.querySelector) {//This is ONLY a test to see, if Internet Explorer IE7 or below. IF IE7 or lower: we have a big problem with this routine copying the items of the form. I got no clue why. It has to do that in IE7 all weird attributes are present. Therefore, we have to select only the ones we need.
                    if (key.toLowerCase() != 'method' && key.toLowerCase() != 'action' && key.toLowerCase() != 'class' && key.toLowerCase() != 'name' && key.toLowerCase() != 'target'){
                        continue;    
                    }
                }
                if (key =='class') formObjAttributes[key] = ' nossl_encrypted_submit_directly'; //We overwrite all classes with only this one class: Later we can just submit this artificial form and it wont be reevaluated
                copiedForm += ' '+key+' ="'+formObjAttributes[key]+'"';    
            }
        }
        copiedForm += ' >'+'<input id="nossl_inp_enc" type="hidden" name="nossl_encrypted_form_values" value="'+that.encrypt(field_values)+'" /></form></div>';
        debug('Here is the copiedForm: '+copiedForm);
        $('body').append(copiedForm);
        $('#'+formID).submit();
   }
}

