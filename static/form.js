$(function() {
	$().separation([
		{
			"id": "{{$form}}",
			"url": "{{$url}}/json-form/{{$form}}",
			"args": {},
			"hbs": "form-{{$form}}.hbs",
			"target": "content",
			"type": "Form"
		}
	]);
});