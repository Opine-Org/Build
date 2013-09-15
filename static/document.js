$(function() {
	$().separation([
		{
			"id": "{{$plural}}",
			"url": "{{$url}}/json-data/{{$plural}}/bySlug/:slug",
			"args": {},
			"hbs": "{{$singular}}.hbs",
			"target": "content",
			"type": "Document"
		}
	]);
});