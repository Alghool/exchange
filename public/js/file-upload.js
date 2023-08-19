$(document).ready(function() {
	$('.custom-file-input').on('change',function(event){
		var files = event.target.files;
		for (var i = 0; i < files.length; i++) {
			var file = files[i];
			$(this).next('.custom-file-label').addClass("selected").html(file.name);
		}
	});
});