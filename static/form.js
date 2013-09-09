$(function() {
	$().separation([
		{
			"id": "{{$form}}",
			"url": "{{$url}}/json-form/{{$form}}",
			"args": {},
			"hbs": "../partials/form-{{$form}}.hbs",
			"selector": "content",
			"type": "Form"
		}
	]);
});