<?php
/*
Template Name: Other Options Layout
*/
?>

<!-- Begin custom layout -->
<style>
/* Custom CSS for the user options layout */

.user-options-container {
    display: flex;
    flex-wrap: wrap;
    margin: 0 auto;
    max-width: 1200px;
}

.user-options-column {
    flex: 1;
    box-sizing: border-box;
}

.user-options-left {
    flex: 0 0 50%;
    min-height: 100vh;
    background-image: url('https://you.fearlessliving.org/wp-content/uploads/2022/09/download.jpg');
    background-size: cover;
    background-position: center;


.user-options-right {
    flex: 0 0 50%;
    background-color: #ffffff;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.user-options-logo {
    width: 75%;
    Max-width: 250px
    height: auto;
    margin-bottom: 20px;
}

.user-options-form {
    width: 100%;
}

.user-options-label {
    font-weight: bold;
    margin-bottom: 10px;
    display: block;
}

.user-options-input {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.user-options-radio-group {
    margin-bottom: 20px;
}

.user-options-radio {
    margin-right: 10px;
}

.user-options-radio-label {
    margin-right: 20px;
}

.user-options-button {
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.user-options-button:hover {
    background-color: #0056b3;
}

@media (max-width: 768px) {
    .user-options-left, .user-options-right {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .user-options-right {
        padding: 20px;
    }
}
</style>

<div class="user-options-container">
    <!-- Left side with background image -->
    <div class="user-options-column user-options-left">
        <!-- You can add additional content here if needed -->
    </div>
    
    <!-- Right side with white background -->
    <div class="user-options-column user-options-right">
<img src="https://you.fearlessliving.org/wp-content/uploads/2022/09/fy_dk_teal.png" alt="Logo" class="user-options-logo" />
        <!-- Form content -->
        <form id="user-options-form" class="user-options-form" action="" method="post">
            <label for="first_name" class="user-options-label">First Name</label>
            <input type="text" name="first_name" id="first_name" class="user-options-input" required />

            <label for="user_email" class="user-options-label">Email Address</label>
            <input type="email" name="user_email" id="user_email" class="user-options-input" required />

            <div class="user-options-radio-group">
                <label for="option" class="user-options-label">Choose an option:</label>
                <input type="radio" id="forgot_password" name="option" value="forgot_password" class="user-options-radio" required>
                <label for="forgot_password" class="user-options-radio-label">Forgot Password</label><br>
                
                <input type="radio" id="delete_account" name="option" value="delete_account" class="user-options-radio" required>
                <label for="delete_account" class="user-options-radio-label">Delete Account</label><br>
                
                <input type="radio" id="export_data" name="option" value="export_data" class="user-options-radio" required>
                <label for="export_data" class="user-options-radio-label">Export Data</label><br>
            </div>
            
            <input type="submit" name="submit_option" id="submit_option" class="user-options-button" value="Submit" />
        </form>
    </div>
</div>

?>
