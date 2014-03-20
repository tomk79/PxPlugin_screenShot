(function() {
	console.debug(phantom.args);
	var fs = require('fs');
	var options = {
		url: phantom.args[0],
		saveTo: phantom.args[1],
		width: phantom.args[2],
		height: phantom.args[3],
		userAgent: phantom.args[4]
	};

	if( !options.url ){
		console.log("[ERROR] No URL given."+"\n");
		return phantom.exit();
	}
	if( !options.saveTo ){
		console.log("[ERROR] No output path given."+"\n");
		return phantom.exit();
	}
	if( !options.width ){
		options.width = 1024;
	}
	if( !options.height ){
		options.height = 100;
	}
	if( !options.userAgent ){
		options.userAgent = 'Google Chrome';
	}

	var webpage = require('webpage').create();
	webpage.viewportSize = {
		width: options.width,
		height: options.height
	};
	webpage.settings.userAgent = options.userAgent;
	webpage.paperSize = {
		format: 'A3',
		orientation: 'portrait',
		border: '1cm'
	};
	webpage.onLoadFinished = function() {
		console.log('page Load Finished.');
	};
	webpage.open(options.url, function(status) {
		if (status === 'success') {
			window.setTimeout( function(){
				if( webpage.render( options.saveTo ) ){//←保存先パスの拡張子が *.pdf であれば
					console.log('Success!');
				}else{
					console.log('Error: Disable to save image file.');
				}
				return phantom.exit();
			}, 500 );
			return;
		}else{
			console.log('Error: on page loading. ('+status+' : '+options.url+')');
			return phantom.exit();
		}
	});

}).call(this);
