<?php
/**
 * Template Name: Other Options
 */

get_header();
?>

<div id="primary" class="content-area">
    <div id="content" class="site-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="other-options-container">
                        <h1 class="page-title">Account Options</h1>
                        
                        <!-- Email Input -->
                        <div class="email-form-section">
                            <p>Enter your email address to proceed:</p>
                            <div class="form-group">
                                <input type="email" id="user-email" class="form-control" placeholder="your@email.com" required>
                            </div>
                        </div>
                        
                        <!-- Option Selection -->
                        <div class="options-section" id="options-section" style="display: none;">
                            <h3>What would you like to do?</h3>
                            
                            <div class="option-card" data-option="reset-password">
                                <div class="option-icon">
                                    <i class="bb-icon-key"></i>
                                </div>
                                <div class="option-content">
                                    <h4>Reset Password</h4>
                                    <p>Get a password reset link sent to your email</p>
                                </div>
                            </div>
                            
                            <div class="option-card" data-option="export-data">
                                <div class="option-icon">
                                    <i class="bb-icon-download"></i>
                                </div>
                                <div class="option-content">
                                    <h4>Export User Data</h4>
                                    <p>Download all your account data and content</p>
                                </div>
                            </div>
                            
                            <div class="option-card" data-option="delete-account">
                                <div class="option-icon">
                                    <i class="bb-icon-trash"></i>
                                </div>
                                <div class="option-content">
                                    <h4>Delete Account</h4>
                                    <p>Permanently remove your account and all data</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button type="button" id="continue-btn" class="btn btn-primary" style="display: none;">Continue</button>
                            <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">Back to Login</a>
                        </div>
                        
                        <!-- Messages -->
                        <div id="message-container" class="message-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.other-options-container {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-top: 40px;
}

.page-title {
    text-align: center;
    margin-bottom: 30px;
    color: #122B46;
    font-size: 28px;
    font-weight: 600;
}

.email-form-section {
    margin-bottom: 30px;
}

.email-form-section p {
    margin-bottom: 15px;
    color: #7F868F;
    font-size: 16px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #E7E9EC;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #007CFF;
    box-shadow: 0 0 0 2px rgba(0, 124, 255, 0.1);
}

.options-section {
    margin-bottom: 30px;
}

.options-section h3 {
    margin-bottom: 20px;
    color: #122B46;
    font-size: 20px;
}

.option-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border: 2px solid #E7E9EC;
    border-radius: 8px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.option-card:hover {
    border-color: #007CFF;
    background-color: #F8FAFE;
}

.option-card.selected {
    border-color: #007CFF;
    background-color: #F8FAFE;
}

.option-icon {
    margin-right: 15px;
    font-size: 24px;
    color: #007CFF;
    width: 40px;
    text-align: center;
}

.option-content h4 {
    margin: 0 0 5px 0;
    color: #122B46;
    font-size: 18px;
    font-weight: 600;
}

.option-content p {
    margin: 0;
    color: #7F868F;
    font-size: 14px;
}

.action-buttons {
    text-align: center;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin: 0 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #007CFF;
    color: white;
}

.btn-primary:hover {
    background-color: #0056CC;
}

.btn-secondary {
    background-color: #E7E9EC;
    color: #122B46;
}

.btn-secondary:hover {
    background-color: #D1D5DB;
}

.message-container {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
    display: none;
}

.message-container.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message-container.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.message-container.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<script>
jQuery(document).ready(function($) {
    let selectedOption = null;
    let userEmail = null;
    
    // Email validation
    $('#user-email').on('input', function() {
        const email = $(this).val();
        if (isValidEmail(email)) {
            userEmail = email;
            $('#options-section').show();
        } else {
            userEmail = null;
            $('#options-section').hide();
            $('#continue-btn').hide();
        }
    });
    
    // Option selection
    $('.option-card').on('click', function() {
        $('.option-card').removeClass('selected');
        $(this).addClass('selected');
        selectedOption = $(this).data('option');
        $('#continue-btn').show();
    });
    
    // Continue button
    $('#continue-btn').on('click', function() {
        if (!userEmail || !selectedOption) {
            showMessage('Please enter your email and select an option.', 'error');
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'handle_other_options',
                email: userEmail,
                option: selectedOption,
                nonce: '<?php echo wp_create_nonce('other_options_nonce'); ?>'
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Continue');
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Reset form
                    $('#user-email').val('');
                    $('#options-section').hide();
                    $('#continue-btn').hide();
                    $('.option-card').removeClass('selected');
                    selectedOption = null;
                    userEmail = null;
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Continue');
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showMessage(message, type) {
        $('#message-container')
            .removeClass('success error info')
            .addClass(type)
            .text(message)
            .show();
            
        setTimeout(function() {
            $('#message-container').fadeOut();
        }, 5000);
    }
});
</script>

<?php get_footer(); ?>