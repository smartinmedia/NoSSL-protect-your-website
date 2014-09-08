/*
Copyright (c) 2011 Wojo Design
Dual licensed under the MIT or GPL licenses.
*/
(function(){
var window = this;

/*
 * 
 * This adds session and global storage to browsers, which dont support it natively!
 * 
 */

//Added by SIM: Store the result of this procedure!
//sessionStorage variable "storeMethod": "standard" (if local storage is there), "global", if global storage or "userdata"


// check to see if we have sessionStorage or not
if( !window.sessionStorage ){	

// globalStorage
// non-standard: Firefox 2+
// https://developer.mozilla.org/en/dom/storage#globalStorage
if ( window.globalStorage ) {
// try/catch for file protocol in Firefox
try {
window.sessionStorage = window.globalStorage;
} catch( e ) {}
sessionStorage.setItem("nossl_storeMethod", "global");
return;
}

// userData
// non-standard: IE 5+
// http://msdn.microsoft.com/en-us/library/ms531424(v=vs.85).aspx
var div = document.createElement( "div" ),
attrKey = "sessionStorage";
div.style.display = "none";
document.getElementsByTagName( "head" )[ 0 ].appendChild( div );
if ( div.addBehavior ) {
div.addBehavior( "#default#userdata" );
//div.style.behavior = "url('#default#userData')";

var sessionStorage = window["sessionStorage"] = {
"length":0,
"setItem":function( key , value ){
div.load( attrKey );
key = cleanKey(key );

if( !div.getAttribute( key ) ){
this.length++;
}
div.setAttribute( key , value );

div.save( attrKey );
},
"getItem":function( key ){
div.load( attrKey );
key = cleanKey(key );
return div.getAttribute( key );

},
"removeItem":function( key ){
div.load( attrKey );
key = cleanKey(key );
div.removeAttribute( key );

div.save( attrKey );
this.length--;
if( this.length < 0){
this.length=0;
}
},

"clear":function(){
div.load( attrKey );
var i = 0;
while ( attr = div.XMLDocument.documentElement.attributes[ i++ ] ) {
div.removeAttribute( attr.name );
}
div.save( attrKey );
this.length=0;
},

"key":function( key ){
div.load( attrKey );
return div.XMLDocument.documentElement.attributes[ key ];
}

},

// convert invalid characters to dashes
// http://www.w3.org/TR/REC-xml/#NT-Name
// simplified to assume the starting character is valid
cleanKey = function( key ){
return key.replace( /[^-._0-9A-Za-z\xb7\xc0-\xd6\xd8-\xf6\xf8-\u037d\u37f-\u1fff\u200c-\u200d\u203f\u2040\u2070-\u218f]/g, "-" );
};


div.load( attrKey );
sessionStorage["length"] = div.XMLDocument.documentElement.attributes.length;
sessionStorage.setItem("nossl_storeMethod", "userdata");

}
}
else { //Added by SIM: if local Storage is present
      window.sessionStorage.setItem("nossl_storeMethod", "standard");
                
}

})();

