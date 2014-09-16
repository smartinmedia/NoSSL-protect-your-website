/*!
 * mapAttributes jQuery Plugin v1.0.0
 *
 * Copyright 2010, Michael Riddle
 * Licensed under the MIT
 * http://jquery.org/license
 *
 * Date: Sun Mar 28 05:49:39 2010 -0900
 * 
 * Just adds "mapAttributes and listAttributes" to Jquery
 */






(function($) {
    $.fn.nossl_getAttributes = function() {
        var attributes = {}; 

        if( this.length ) {
            $.each( this[0].attributes, function( index, attr ) {
                attributes[ attr.name ] = attr.value;
            } ); 
        }

        return attributes;
    };
})(jQuery);





function nossl_countProperties(obj) {
    var count = 0;
        for(var prop in obj) {
            if (Object.prototype.hasOwnProperty.call(obj,prop))
            //if(obj.hasOwnProperty(prop))
                ++count;
    }

    return count;
}

		jQuery.fn.mapAttributes = function(prefix) {
			var maps = [];
			$(this).each(function() {
				var map = {};
				var number_of_rounds = nossl_countProperties(this.attributes); var j=0;
                for(var key in this.attributes) {
				    j++; 
                    if (j>number_of_rounds) break;
					if(!isNaN(key)) {
						if(!prefix || this.attributes[key].name.substr(0,prefix.length) == prefix) {
							map[this.attributes[key].name] = this.attributes[key].value;
						}
					}
				}
				maps.push(map);
			});
			return (maps.length > 1 ? maps : maps[0]);
		}


/*!
 * listAttributes jQuery Plugin v1.1.0
 *
 * Copyright 2010, Michael Riddle
 * Licensed under the MIT
 * http://jquery.org/license
 *
 * Date: Sun Mar 28 05:49:39 2010 -0900
 */
		jQuery.fn.listAttributes = function(prefix) {
			var list = [];
			$(this).each(function() {
				console.info(this);
				var attributes = [];
				for(var key in this.attributes) {
					if(!isNaN(key)) {
						if(!prefix || this.attributes[key].name.substr(0,prefix.length) == prefix) {
							attributes.push(this.attributes[key].name);
						}
					}
				}
				list.push(attributes);
			});
			return (list.length > 1 ? list : list[0]);
		}
