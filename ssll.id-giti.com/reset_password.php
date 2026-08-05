<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e0e4ee;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .form-group {
            margin-bottom: 15px;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 30px;
            text-align: center;
        }

        form label {
            display: block;
            margin-bottom: 10px;
            margin-left: 5%;
        }

        form input {
            width: 85%;
            margin-left: 5%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        form button {
            width: auto;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: bold;
            background-color: #1c768f;
            color: #fff;
            cursor: pointer;
        }

        form button:hover {
            background-color: #228daa;
        }

        .button-group {
            text-align: center;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .form-container {
                max-width: 80%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Reset Password</h1>
            <form action="process_reset_password.php" method="post">
                <div class="form-group">
                    <input type="hidden" name="username" value="<?php echo isset($_GET['username']) ? $_GET['username'] : ''; ?>">
                    <input type="hidden" name="otp" value="<?php echo isset($_GET['otp']) ? $_GET['otp'] : ''; ?>">
                    <label for="new-password">New Password</label>
                    <input type="password" name="new-password" id="new-password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" name="confirm-password" id="confirm-password" required>
                </div>
                <div class="button-group">
                    <button type="submit">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
