$(function() {
	$().separation([
		{
			"id": "{{$form}}",
			"url": "{{$url}}/json-form/{{$form}}",
			"args": {},
			"hbs": "form-{{$form}}.hbs",
			"selector": "content",
			"type": "Form"
		}
	]);
});