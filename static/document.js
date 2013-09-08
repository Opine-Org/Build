$(function() {
	$().separation([
		{
			"id": "{{$collection}}",
			"url": "{{$url}}/json/{{$collection}}/bySlug/:slug",
			"args": {},
			"hbs": "../partials/{{$singular}}.hbs",
			"selector": "content",
			"type": "Document"
		}
	]);
});