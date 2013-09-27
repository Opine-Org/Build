var p='';if(window.location.protocol=='file:'){p=window.location.pathname.split('/layouts/')[0];}
require([p+"/js/jquery.min.js",p+"/js/jquery.form.js",p+"/js/jquery.ba-hashchange.js",p+"/js/handlebars.min.js",p+"/js/jquery.separation.js"], function() {
	$(function() {
		$().separation([
			{
				"id": "{{$form}}",
				"url": "{{$url}}/json-form/{{$form}}",
				"args": {},
				"hbs": "form-{{$form}}.hbs",
				"type": "Form"
			}
		]);
	});
});