<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Order Canceled</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f6f6f6;
      margin: 0;
      padding: 0;
    }
    .email-container {
      max-width: 600px;
      margin: 30px auto;
      background-color: #ffffff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .header {
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 1px solid #eee;
    }
    .header h1 {
      color: #e74c3c;
      font-size: 24px;
      margin: 0;
    }
    .content {
      padding: 20px 0;
      color: #333;
      line-height: 1.6;
    }
    .reason {
      background-color: #fce4e4;
      color: #c0392b;
      padding: 15px;
      border-radius: 5px;
      margin-top: 10px;
      font-weight: bold;
    }
    .footer {
      text-align: center;
      color: #999;
      font-size: 12px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <h1>Order Canceled</h1>
    </div>
    <div class="content">
      <p>Dear {{ $data['name'] }},</p>
      <p>We want to inform you that your order has been <strong>canceled</strong>.</p>
      <div class="reason">
        Reason: {{ $data['reason'] }}
      </div>
      <p>If you have any questions, please contact our support team.</p>
    </div>
    <div class="footer">
      &copy; {{ date("Y") }} {{ env('APP_NAME') }}. All rights reserved.
    </div>
  </div>
</body>
</html>
