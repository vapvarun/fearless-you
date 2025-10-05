<?php
/**
 * Category Separator Template Part
 * 
 * Displays a visual separator with category-specific colors
 * 
 * @package FearlessLiving
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get current post categories
$categories = get_the_category();
if ( empty( $categories ) ) {
    return;
}

$primary_category = $categories[0];
$category_color = fli_get_category_color( $primary_category->slug );
$category_class = fli_category_color_class( $primary_category->slug );
?>

<div class="category-separator-wrapper <?php echo esc_attr( $category_class ); ?>">
    <div class="category-separator" style="background-color: <?php echo esc_attr( $category_color ); ?>;"></div>
    <div class="category-separator-label">
        <span class="category-badge" style="background-color: <?php echo esc_attr( $category_color ); ?>; color: <?php echo esc_attr( fli_get_contrast_color( $category_color ) ); ?>;">
            <?php echo esc_html( $primary_category->name ); ?>
        </span>
    </div>
</div>

<style>
.category-separator-wrapper {
    margin: 30px 0;
    text-align: center;
}

.category-separator {
    height: 3px;
    width: 100%;
    border-radius: 2px;
    margin-bottom: 15px;
    position: relative;
}

.category-separator::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    background-color: inherit;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px currentColor;
}

.category-separator-label {
    margin-top: 10px;
}

.category-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Category-specific hover effects */
.category-separator-wrapper:hover .category-separator {
    transform: scaleY(1.5);
    transition: transform 0.3s ease;
}

.category-separator-wrapper:hover .category-badge {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}
</style>

<?php
/**
 * Helper function to get contrast color
 */
if ( ! function_exists( 'fli_get_contrast_color' ) ) {
    function fli_get_contrast_color( $hex ) {
        $hex = str_replace( '#', '', $hex );
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        
        // Calculate luminance
        $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
        
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
}
?>