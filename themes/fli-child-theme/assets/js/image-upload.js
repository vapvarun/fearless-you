/**
 * Image Upload JavaScript Handler
 */
jQuery(document).ready(function ($) {
	$('.fli-image-upload-form').each(function () {
		const form = $(this);
		const fileInput = form.find('.fli-image-input');
		const uploadButton = form.find('.fli-upload-button');
		const submitButton = form.find('.fli-submit-upload');
		const previewArea = form.find('.fli-preview-area');
		const previewImage = form.find('.fli-preview-image');
		const removeButton = form.find('.fli-remove-image');
		const progressArea = form.find('.fli-upload-progress');
		const progressBar = form.find('.fli-progress-bar');
		const messageArea = form.find('.fli-upload-message');
		const maxSize = parseInt(form.data('max-size')) * 1024 * 1024; // Convert MB to bytes

		let selectedFile = null;

		// Handle upload button click
		uploadButton.on('click', function (e) {
			e.preventDefault();
			fileInput.trigger('click');
		});

		// Handle file selection
		fileInput.on('change', function (e) {
			const file = e.target.files[0];

			if (!file) {
				return;
			}

			// Validate file type
			const allowedTypes = [
				'image/jpeg',
				'image/jpg',
				'image/png',
				'image/gif',
				'image/webp',
			];
			if (!allowedTypes.includes(file.type)) {
				showMessage(fli_upload.type_error, 'error');
				return;
			}

			// Validate file size
			if (file.size > maxSize) {
				showMessage(fli_upload.max_size_error, 'error');
				return;
			}

			selectedFile = file;

			// Show preview
			if (previewArea.length) {
				const reader = new FileReader();
				reader.onload = function (e) {
					previewImage.attr('src', e.target.result);
					previewArea.show();
					submitButton.show();
				};
				reader.readAsDataURL(file);
			} else {
				submitButton.show();
			}
		});

		// Handle remove image
		removeButton.on('click', function (e) {
			e.preventDefault();
			selectedFile = null;
			fileInput.val('');
			previewArea.hide();
			submitButton.hide();
			messageArea.hide();
		});

		// Handle form submission
		form.on('submit', function (e) {
			e.preventDefault();

			if (!selectedFile) {
				showMessage('Please select an image', 'error');
				return;
			}

			// Prepare form data
			const formData = new FormData();
			formData.append('action', 'fli_upload_image');
			formData.append('nonce', fli_upload.nonce);
			formData.append('image', selectedFile);

			// Disable buttons
			uploadButton.prop('disabled', true);
			submitButton.prop('disabled', true).text(fli_upload.uploading);

			// Show progress
			progressArea.show();
			progressBar.css('width', '0%');

			// Upload via AJAX
			$.ajax({
				url: fli_upload.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				xhr() {
					const xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener(
						'progress',
						function (e) {
							if (e.lengthComputable) {
								const percentComplete =
									(e.loaded / e.total) * 100;
								progressBar.css('width', percentComplete + '%');
							}
						},
						false
					);
					return xhr;
				},
				success(response) {
					if (response.success) {
						showMessage('Image uploaded successfully!', 'success');

						// Trigger custom event with upload data
						$(document).trigger('fli_image_uploaded', [
							response.data,
						]);

						// Reset form after delay
						setTimeout(function () {
							resetForm();
						}, 2000);
					} else {
						showMessage(
							response.data.message || fli_upload.upload_error,
							'error'
						);
						resetButtons();
					}
				},
				error() {
					showMessage(fli_upload.upload_error, 'error');
					resetButtons();
				},
				complete() {
					progressArea.hide();
				},
			});
		});

		// Helper functions
		function showMessage(message, type) {
			messageArea
				.removeClass('success error')
				.addClass(type)
				.text(message)
				.show();
		}

		function resetForm() {
			selectedFile = null;
			fileInput.val('');
			previewArea.hide();
			submitButton.hide();
			messageArea.hide();
			resetButtons();
		}

		function resetButtons() {
			uploadButton.prop('disabled', false);
			submitButton.prop('disabled', false).text('Upload Image');
		}
	});

	// Gallery shortcode handler
	$('.fli-image-gallery').each(function () {
		const gallery = $(this);
		const uploadArea = gallery.find('.fli-gallery-upload');
		const imagesContainer = gallery.find('.fli-gallery-images');

		// Listen for uploaded images
		$(document).on('fli_image_uploaded', function (e, data) {
			if (
				uploadArea.length &&
				uploadArea.closest('.fli-image-gallery').is(gallery)
			) {
				// Add image to gallery
				const imageHtml = `
                    <div class="fli-gallery-item" data-id="${data.attachment_id}">
                        <img src="${data.urls.medium}" alt="${data.filename}">
                        <button class="fli-gallery-remove" data-id="${data.attachment_id}">×</button>
                    </div>
                `;
				imagesContainer.append(imageHtml);
			}
		});

		// Handle remove from gallery
		gallery.on('click', '.fli-gallery-remove', function (e) {
			e.preventDefault();
			const item = $(this).closest('.fli-gallery-item');
			const attachmentId = $(this).data('id');

			if (confirm('Remove this image from gallery?')) {
				item.fadeOut(300, function () {
					$(this).remove();
				});

				// Trigger custom event
				$(document).trigger('fli_gallery_image_removed', [
					attachmentId,
				]);
			}
		});
	});
});
