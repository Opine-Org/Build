$(function() {
	$().separation([
		{
			"id": "{{$plural}}",
			"url": "{{$url}}/json-data/{{$plural}}/bySlug/:slug",
			"args": {},
			"hbs": "../partials/{{$singular}}.hbs",
			"selector": "content",
			"type": "Document"
		}
	]);
});