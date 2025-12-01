<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cashier Limit Notification</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            width: 100%;
            max-width: 550px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #ff4d4f;
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
        }
        .content {
            margin-top: 20px;
            font-size: 15px;
            color: #333;
        }
        .item {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            color: #444;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        Cashier Limit Exceeded
    </div>

    <div class="content">
        <p class="item">
            <span class="label">Cashier Name:</span>
            {{ $name }}
        </p>

        <p class="item">
            <span class="label">Total at Shift:</span>
            {{ $total }}
        </p>

        <p class="item">
            <span class="label">Amount at This Order:</span>
            {{ $amount }}
        </p>

        <p style="margin-top:20px;">
            The cashier has exceeded the allowed limit at Free Discount.  
            Please review the transaction urgently.
        </p>
    </div>

    <div class="footer">
        This is an automated system notification.
    </div>
</div>

</body>
</html>
