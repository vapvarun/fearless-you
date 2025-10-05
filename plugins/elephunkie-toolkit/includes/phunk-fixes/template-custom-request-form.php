<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Form</title>
    <style>
        body {
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .custom-modal {
            background-color: rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .custom-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 350px;
            text-align: center;
        }
        .custom-form input, .custom-form select {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .custom-form input[type="submit"] {
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .custom-form input[type="submit"]:hover {
            background-color: #005f8d;
        }
        a {
            display: block;