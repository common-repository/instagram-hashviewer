var Util = Util || {};
Util.removeLeadingHash = function (str) {
	if (typeof str == "string" && str.charAt(0) == '#') {
		return str.substring(1);
	}
	return str;
};


var HashViewer = HashViewer || {};
HashViewer.next_url = undefined;
HashViewer.next_max_tag_id = undefined;
HashViewer.last_tag = '';
HashViewer.no_of_pictures = 0;
HashViewer.CLIENT_ID = "d81afea83c3f40b5a5485418e2a53aa7";

HashViewer.reset = function() {
	console.log("reset called");
	HashViewer.next_url = undefined;
	HashViewer.next_max_tag_id = undefined;
	HashViewer.last_tag = '';
	HashViewer.no_of_pictures = 0;
	jQuery("#gallery").html('');
	jQuery("#more-btn").addClass('hidden');
};

HashViewer.getInputField = function () {
	return jQuery("input[id='tag-text']");
};
HashViewer.getInputValue = function () {
	return HashViewer.getInputField().val();
};
HashViewer.setInputValue = function (val) {
	HashViewer.getInputField().val(val);
};


HashViewer.splitHashtags = function(text) {
	var result = text[0];
	for(var i = 1; i < text.length; i++) {
		if(text[i]=='#' && text[i-1]!=' ') {
			result += ' ';
		}
		result += text[i];
	}
	return result;
};


HashViewer.createGalleryBlock = function(post) {
	var image = post.images.low_resolution;
	var user = post.user;
	var caption = post.caption;

	var out = '<div width="'+image.width+'px" class="gallery-block text-center">';
		out +=		'<a href="'+post.link+'"><img width="'+image.width+'px" class="gallery-image col-sm-12" src ="'+image.url+'" /></a>';
		out +=		'<em>User: <a href="http://instagram.com/'+user.username+'">'+user.username+'</a></em>';
		/* if (caption) {
			out +=	'<p>'+this.splitHashtags(caption.text)+'</p>';
		} */
		out +=  '</div>'; //width-fix END
	
	return out;
};

HashViewer.displayError = function(message) {
	jQuery('#error-container').html('<span>'+message+'</span>').removeClass('hidden');
	console.log(message);
};

HashViewer.updateGallery = function(in_tag) {
	console.log("updateGallery called");
	jQuery('#error-container').addClass('hidden'); // Hide old errors
	var tag = in_tag || HashViewer.last_tag || HashViewer.getInputValue();
	tag = Util.removeLeadingHash(tag);

	if (tag === "") return;
	if (HashViewer.last_tag != tag)
		HashViewer.reset();
	HashViewer.last_tag = tag;


	if (HashViewer.getInputValue() === "") 
		HashViewer.setInputValue(tag); // Set search field to location hash when navigating directly to search

	HashViewer.next_url = 'https://api.instagram.com/v1/tags/'+tag+'/media/recent?client_id='+HashViewer.CLIENT_ID;
	console.log(HashViewer.next_url);
	if (HashViewer.next_max_tag_id) 
		HashViewer.next_url += "&max_tag_id="+HashViewer.next_max_tag_id;

	jQuery.ajax({
		url: HashViewer.next_url,
		type: 'get',
		dataType: 'jsonp'
		})
		.done(function(res) {
			if (res.meta.code >= 400) { // if requests responds with HTTP error codes
				HashViewer.displayError("ERROR: "+res.meta.error_message);
			} else {
				jQuery.each(res.data, function(i, post) {
					jQuery("#gallery").append(HashViewer.createGalleryBlock(post));
					HashViewer.no_of_pictures += 1;
				});

				if (res.pagination.next_max_tag_id) {
					jQuery("#more-btn").removeClass('hidden');
					HashViewer.next_max_tag_id = res.pagination.next_max_tag_id;
				} else {
					jQuery("#more-btn").addClass('hidden');
				}
			}

		})
		.fail(function(err) {
			console.log("fail: " + HashViewer.next_url);
			HashViewer.displayError("FAILURE:"+err);
		})
		.error(function(XHR, status, err) {
			console.log("error: " + HashViewer.next_url);
			HashViewer.displayError("ERROR:"+err);
	});
	
	return this;
};

jQuery(document).ready(function($) {
	// $("button[id='tag-btn']").bind('click', HashViewer.updateWindowHash()); 
	HashViewer.getInputField().keypress(function (e) { // enter-fix for search
        if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
            $("button[id='tag-btn']").click();
            $(this).blur();	
            return false;
        } else {
            return true;
        }
    });
});