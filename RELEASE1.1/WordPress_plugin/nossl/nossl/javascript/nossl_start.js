var nossl = new NoSSL(); //Generate a NoSSL Object

//This is for automatically encrypting everything that is sent via AJAX
$.ajaxSetup({
            beforeSend: function(jqXHR, settings) {
                if (typeof settings.data ==="object"){
                    settings.data = JSON.stringify(settings.data);
                    }
                settings.data = 'nossl_encrypted_ajax='+encodeURIComponent(nossl.encrypt(settings.data));
                return true;
            }
      });


$('document').ready(function(){
    //nossl.parseServerSettings($('#nossl_serversettings').text());
    nossl.initializeClient();


    if($('.nossl_echo_encrypted')[0]){//This is the class of the nossl_echo
            $( ".nossl_echo_encrypted" ).each(function( index ) {
                var content = nossl.decrypt($(this).text());
                $(this).replaceWith(content);
            });

        }
    
 
    $(document).on('submit', 'form:not(".nossl_encrypted_submit_directly"):not(".nossl_disable_protection")', function(event){
            //if ($( this ).hasClass( "nossl_encrypted_submit_directly" )) console.log('has class');
            nossl.formEncrypt($(this));
            event.preventDefault();
            return false;
    });
});




