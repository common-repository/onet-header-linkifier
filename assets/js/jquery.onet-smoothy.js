/**
* ONet Smoothy is a internal link scroll smoother
* If your load contents via ajax you should call this function again to bind Smoothy to newly appended links.
* If you have any trouble using Smoothy, try to turn it on in the WP admin
**/
jQuery.fn.onetSmoothy = function () {
	// Retrieve self url
	var locNoHash = window.location.href.substr(0,window.location.href.indexOf("#"));
	// Gather all anchors from the page
	var links = jQuery("a");

	links.each(function () {
		// Define main variables
		var link = jQuery(this);
		var href = link.prop("href").toString();

		// Skip if already "converted", this is important for reinits after Ajax update
		if (link.hasClass("onet-smootify")) return;

		// Skip link if href attr is undefined/empty or contains no hash
		if (typeof href === "undefined" || href.length < 1 || href == "#" || !href.match(/\#/)) return;

		// Export matches and also check again if hash is valid
		var match = link.attr("href").toString().match(/^([^\#]*)\#(.+)$/i);
		if (!match) return;
		
		// Bind click function
		if ( (match[1].length == 0 || match[1] == locNoHash) && match[2].length > 0 ) {
			link.click(function (e) {
				
				target = null;
				hash_raw = jQuery(this).prop("href");
				hash = hash_raw.substr(hash_raw.indexOf("#")+1);
				
				// Find out matching method
				v1 = jQuery("#"+hash); // modern ID based search
				if (v1.length) target = v1.offset().top;
				// this "else" used to avoid unnecessary resource consumption
				else {
					v2 = jQuery("a[name='"+hash+"']"); // old fashion "anchor" finder
					if (v2.length) target = v2.offset().top;
				}

				// Correct Wordpress admin bar spacing issue
				if (jQuery("#wpadminbar").length > 0 && target != null) target -= 30;

				// If target exists perform smooth scroll
				if (target != null) {
					e.preventDefault();

					// If history can be modified
					if (history.pushState) history.pushState(null, null, '#'+hash);
					else location.hash = '#'+hash;

					// Then perform the scroll
					jQuery("html, body").animate({scrollTop: target},200);
				}
			}).addClass("onet-smootify");
		}
	});
}

// Execure ONet Smoothy script on document ready
jQuery(document).ready(jQuery.fn.onetSmoothy);