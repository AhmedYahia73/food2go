<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9f7ef;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            background: white;
            padding: 40px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }

        .message-box {
            margin: 0 auto;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
        }

        .success-icon {
            font-size: 50px;
            color: #28a745;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }

        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #218838;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="message-box">
            <i class="fa fa-check-circle success-icon"></i>
            <h1>Payment Successful!</h1>
            <p id="success-message"></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Simulating the JSON response from the server
        const response = { success: 'You have successfully completed the process!' };
        // Displaying the success message in the HTML
        document.getElementById('success-message').textContent = response.success;
    });
    setTimeout(() => {
        window.close();
    }, 3000); // Delay of 2 seconds (2000 milliseconds)
</script>
</html>
