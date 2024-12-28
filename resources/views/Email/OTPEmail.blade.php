<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        .code {
            background-color: #f0f0f0;
            border-left: 5px solid #007BFF;
            padding: 10px;
            margin: 20px 0;
            font-size: 1.2em;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="email-container">
        @if (isset($data['name']))
            <h1>Hello, [{{$data['name']}}]!</h1>
        @endif
        <p>Thank you for joining us. Your unique code is:</p>
        <div class="code">[{{$data['code']}}]</div>
        <p>If you have any questions, feel free to reach out!</p>
        <div class="footer">Best Regards</div>
    </div>
</body>
</html>
