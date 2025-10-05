<?php
/*
Template Name: Thank You
Version: 2.0
Description: Automatically logs the user in or creates the user, with fallback if AJAX fails.
*/

// Capture and decode URL parameters
$contact_id = isset($_GET['contactId']) ? urldecode(sanitize_text_field($_GET['contactId'])) : '';
$email = isset($_GET['inf_field_Email']) ? urldecode(sanitize_email($_GET['inf_field_Email'])) : '';
$course_id = isset($_GET['courseId']) ? urldecode(sanitize_text_field($_GET['courseId'])) : '';
$first_name = isset($_GET['inf_field_FirstName']) ? urldecode(sanitize_text_field($_GET['inf_field_FirstName'])) : '';

// Redirect if necessary fields are missing (either contact ID or email is required)
if (empty($contact_id) && empty($email)) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div class="entry-content">
    <h1>Thank You, <?php echo esc_html($first_name); ?>!</h1>
    <p>We are processing your information. Please wait while we set up your account...</p>

    <!-- Progress steps with colored circles -->
    <div id="steps-container" class="steps-container">
        <div id="step1" class="step active">
            <div class="circle" style="background-color: #fde132;">1</div>
            <div class="step-text">Checking if your account exists...</div>
        </div>
        <div id="step2" class="step">
            <div class="circle" style="background-color: #009bde;">2</div>
            <div class="step-text">Confirming your account...</div>
        </div>
        <div id="step3" class="step">
            <div class="circle" style="background-color: #ff6b00;">3</div>
            <div class="step-text">Setting up your account...</div>
        </div>
        <div id="step4" class="step">
            <div class="circle" style="background-color: #fde132;">4</div>
            <div class="step-text">Redirecting you to your profile or course...</div>
        </div>
    </div>

    <div id="ajax-result" style="display: none;"></div>

    <!-- Hidden fallback form for server-side user creation/login -->
    <form id="fallback-form" action="<?php echo home_url('/process-fallback/'); ?>" method="POST" style="display:none;">
        <input type="hidden" name="contact_id" value="<?php echo esc_attr($contact_id); ?>">
        <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
        <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
        <input type="hidden" name="first_name" value="<?php echo esc_attr($first_name); ?>">
        <input type="hidden" name="fallback" value="true">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('secure-ajax-nonce'); ?>">
    </form>
</div>

<!-- Styles for steps and color circles -->
<style>
    /* Styling remains the same */
    .steps-container { margin-top: 20px; display: flex; flex-direction: column; gap: 20px; }
    .step { display: flex; align-items: center; padding: 10px; transition: all 0.3s ease; }
    .circle { width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 18px; font-weight: bold; color: white; margin-right: 10px; }
    .step.active .circle { box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
    .step-text { font-size: 16px; color: #333; }
    .step.active .step-text { font-weight: bold; color: green; }
    .header-aside, .buddypanel { display: none; }
    @media (max-width: 600px) { .steps-container { gap: 15px; } .step { flex-direction: column; align-items: flex-start; } .circle { margin-bottom: 5px; } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let attempts = 0;
        const maxAttempts = 10;  // Max polling attempts (10 attempts, 30 seconds total)

        function showNextStep(step) {
            const stepElement = document.getElementById(step);
            if (stepElement) {
                stepElement.classList.add('active');
            }
        }

        function pollForUserStatus(forceCreate = false) {
            const formData = new FormData();
            formData.append('contact_id', decodeURIComponent('<?php echo esc_attr($contact_id); ?>'));
            formData.append('email', decodeURIComponent('<?php echo esc_attr($email); ?>'));
            formData.append('course_id', decodeURIComponent('<?php echo esc_attr($course_id); ?>'));
            formData.append('first_name', decodeURIComponent('<?php echo esc_attr($first_name); ?>')); // Added this line
            formData.append('action', 'check_user_status');
            formData.append('nonce', '<?php echo wp_create_nonce('secure-ajax-nonce'); ?>');

            if (forceCreate) {
                formData.append('force_create', 'true');  // If the user is not found, force-create them
            }

            console.log('Attempting AJAX call, attempt:', attempts + 1);
            console.log('Force create:', forceCreate);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('AJAX Response:', data);
                
                if (data.success) {
                    console.log('Success! Redirecting to:', data.data.redirect_url);
                    showNextStep('step4');  // Show final step
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url;
                    }, 1000);  // Redirect after a short delay
                } else if (attempts < maxAttempts) {
                    // Retry after 3 seconds if under maxAttempts
                    attempts++;
                    console.log('Retrying in 3 seconds, attempt', attempts, 'of', maxAttempts);
                    
                    // Force create user after 5 failed attempts
                    const shouldForceCreate = attempts >= 5;
                    
                    setTimeout(() => pollForUserStatus(shouldForceCreate), 3000);
                    showNextStep('step' + Math.min(attempts + 1, 4));  // Show next step
                } else {
                    // Submit fallback form after max attempts
                    console.log('Max attempts reached, submitting fallback form');
                    document.getElementById('fallback-form').submit();
                }
            })
            .catch(error => {
                console.error('AJAX request failed:', error);
                document.getElementById('ajax-result').innerHTML = 'An error occurred: ' + error.message;
                document.getElementById('ajax-result').style.display = 'block';
                // Submit fallback form if AJAX fails
                console.log('AJAX failed, submitting fallback form');
                document.getElementById('fallback-form').submit();
            });
        }

        // Start polling for user status
        console.log('Starting user status polling...');
        pollForUserStatus();
    });
</script>
</script>

<?php get_footer(); ?>