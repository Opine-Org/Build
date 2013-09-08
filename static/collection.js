$(function() {
	$().separation([
		{
			"id": "{{$plural}}",
			"url": "{{$url}}/json/{{$plural}}/all/10/0/{\"display_date\":-1}",
			"args": {},
			"hbs": "../partials/{{$plural}}.hbs",
			"selector": "content",
			"type": "Collection"
		}
	]);
});