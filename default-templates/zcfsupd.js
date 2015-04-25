/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of zoneclearFeedServer, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2015 Jean-Christian Denis, BG and contributors
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

;if(window.jQuery) (function($) {

	$.fn.zoneclearFeedServer = function(options) {
		var opts = $.extend({}, $.fn.zoneclearFeedServer.defaults, options);
		return this.each(function() {
			$.ajax({ 
				timeout:5000, 
				url:opts.blog_url, 
				type:'POST', 
				data:{blogId:opts.blog_id}/*, 
				error:function(){alert('Feeds update failed');}, 
				succes:function(data){data=$(data);alert($(data).find('message').text());}*/
			}); 
		});
	};
	$.fn.zoneclearFeedServer.defaults = {
		blog_url: '',
		blog_id: ''
	};
})(jQuery);