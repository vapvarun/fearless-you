/*
jQuery(function(s) {
    s(".dd-license-key-toggle").click(function() {
        var t = s(this).prev();
        t.attr("type", "password" === t.attr("type") ? "text" : "password"), s(this).toggleClass("dd-lock dd-unlock")
    }), s(".dd-copy").click(function() {
        var t = s(this).next().text(),
            e = s(this).siblings(".dd-success");
        navigator.clipboard.writeText(t), e.show(), setTimeout(function() {
            e.fadeOut()
        }, 500)
    })
});
*/

document.addEventListener('DOMContentLoaded', function() {
    var elephunkieMenuItem = document.getElementById('toplevel_page_elephunkie-toolkit');
    if (elephunkieMenuItem) {
        elephunkieMenuItem.classList.add('elephunkie-menu-item');
    }
});


jQuery(document).ready(function($){
			//select logo 
			 $('#upload-btn').click(function(e) {
				e.preventDefault();
				var image = wp.media({ 
					title: 'Upload Image',
					// mutiple: true if you want to upload multiple files at once
					multiple: false
				}).open()
				.on('select', function(e){
					// This will return the selected image from the Media Uploader, the result is an object
					var uploaded_image = image.state().get('selection').first();
					// We convert uploaded_image to a JSON object to make accessing it easier
					// Output to the console uploaded_image
					
					var image_url = uploaded_image.toJSON().url;
					// Let's assign the url value to the input field
					$('#image_url').val(image_url);
				});
			});
			//placeholder url 
			 $('#upload-btn2').click(function(e) {
				e.preventDefault();
				var image = wp.media({ 
					title: 'Upload Image',
					multiple: false
				}).open()
				.on('select', function(e){
					var uploaded_image = image.state().get('selection').first();
					var image_url = uploaded_image.toJSON().url;
					$('#placeholder_url').val(image_url);
				});
			});
			
			//color
            $('.color_field').each(function(){
                $(this).wpColorPicker();
            });
 });