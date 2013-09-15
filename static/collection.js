$(function() {
	$().separation([
		{
			"id": "{{$plural}}",
			"url": "{{$url}}/json-data/{{$plural}}/all/10/0/{\"display_date\":-1}",
			"args": {},
			"hbs": "{{$plural}}.hbs",
			"target": "content",
			"type": "Collection"
		}
	]);
});