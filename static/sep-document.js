var p='';if(window.location.protocol=='file:'){p=window.location.pathname.split('/layouts/')[0];}
require([p+"/js/jquery.min.js",p+"/js/jquery.form.js",p+"/js/jquery.ba-hashchange.js",p+"/js/handlebars.min.js",p+"/js/jquery.separation.js",p+"/js/helpers.js"], function() {
	$(function() {
		var url = p+'/app/{{$singular}}.json';
		$.getJSON(url).done(function (data) {
			$().separation(data);
		});
	});
});