$(document).ready(function() {
  // Update the HTML structure of each .fivo-docs-item
  $('.fivo-docs-item').each(function() {
    var $this = $(this);
    
    // Move the file icon span to the new position
    var $icon = $this.find('.fivo-docs-item-icon');
    $icon.css({
      'position': 'absolute',
      'left': '-20px',
      'top': '50%',
      'transform': 'translateY(-50%) rotate(-15deg)',
      'width': '40px',
      'height': '40px'
    });

    // Wrap the entire item in a new div to apply rounded bar styling
    $this.css({
      'background': '#f0f0f0',
      'border-radius': '30px',
      'border': '1px solid var(--fivo-docs--color--border)',
      'padding': '12px 12px 12px 50px',
      'position': 'relative',
      'display': 'flex',
      'gap': '15px',
      'overflow': 'hidden'
    });

    // Ensure consistent hover effect color
    $this.hover(function() {
      $(this).css('background', '#e0e0e0');
    }, function() {
      $(this).css('background', '#f0f0f0');
    });

    // Move and style the icon span within the item
    $this.prepend($icon);
  });
});
