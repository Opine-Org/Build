var collections = [];
$(document).ready(function () {
	$.getJSON('/json-collections?callback=?').done(function (data) {
        collections = data;
        appendEntry();
    });
    $('.collection-table').on('change', '.collection', function () {
    	console.log('changed');
    	for (var i=0; i < collections.length; i++) {
    		if (collections[i]['p'] != $(this).val()) {
    			continue;
    		}
    		options = '<option></option>';
    		for (var j=0; j < collections[i]['methods'].length; j++) {
    			options += '<option value="' + collections[i]['methods'][j] + '">' + collections[i]['methods'][j] + '</option>';
    		}
    		$(this).parents('tr').find('.method').html(options);
    		break;
    	}
    });
    $('.append').click(function () {
		appendEntry();	    	
    });
});

var appendEntry = function () {
	$('.collection-table tbody').append('<tr>\
        <td><input name="id[]" class="form-control" type="text" placeholder="For example: blogs" /></td>\
        <td><select name="collection[]" class="form-control collection"><option>None</option></select></td>\
        <td><select name="method[]" class="form-control method"><option></option></select></td>\
        <td><input name="hbs[]" class="form-control" type="text" placeholder="For example: blogs.hbs" /></td>\
    </tr>');

	$('.collection').each(function (index, element) {
		if ($(element).hasClass('populated')) {
			return true;
		}
		var options = '<option></option>';
		for (var i=0; i < collections.length; i++) {
		   	options += '<option value="' + collections[i]['p'] + '">' + collections[i]['p'] + '</option>';
		}
		$(element).addClass('populated').html(options);
	});
};