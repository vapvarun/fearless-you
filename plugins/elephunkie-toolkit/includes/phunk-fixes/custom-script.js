jQuery(document).ready(function($) {
    $('.login form').hide();
    $('.login #login_error').hide();
    $('.login #nav').hide();
    $('.login #rememberme').hide();
    $('.login .message').hide();

    $('body').append(`
        <div class="custom-request-form">
            <form id="custom-request-form" method="post">
                <input type="hidden" name="phunk_custom_request_nonce" value="${phunk_custom_request.nonce}">
                <label for="phunk_first_name">First Name:</label>
                <input type="text" id="phunk_first_name" name="phunk_first_name" required>
                <label for="phunk_email">Email:</label>
                <input type="email" id="phunk_email" name="phunk_email" required>
                <label for="phunk_request_type">Request Type:</label>
                <select id="phunk_request_type" name="phunk_request_type" required>
                    <option value="delete_account">Delete Account</option>
                    <option value="export_data">Export Data</option>
                </select>
                <input type="submit" name="submit" value="Submit Request">
            </form>
            <p id="backtoblog"><a href="${phunk_custom_request.login_url}">← Back to Sign In</a></p>
        </div>
    `);
});