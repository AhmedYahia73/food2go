<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paymob</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">

    <style>
        :root {
            --background-light: #ffffff;
            --highlight-green: #d7f5da;
            --text-main: #333;
            --text-muted: #555;
        }

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--background-light);
            color: var(--text-main);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }

        .message-box {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10vh 1rem;
        }

        .success-container {
            width: 100%;
            max-width: 600px;
            background: white;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 2rem;
        }

        .success-container img {
            height: 100px;
            margin-bottom: 1rem;
        }

        .success-container h1 {
            color: grey;
            font-size: 1.8rem;
        }

        .confirm-green-box {
            width: 100%;
            height: 140px;
            background: var(--highlight-green);
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        hr {
            border: none;
            height: 1px;
            background-color: #ccc;
            margin: 1rem 0;
        }

        small {
            display: block;
            margin-top: 1rem;
            color: #777;
        }

        .redirect-section {
            margin-top: 2rem;
        }

        @media (max-width: 576px) {
            .success-container {
                padding: 1.5rem;
            }

            .success-container h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const redirectUrl = "{{ $redirectUrl }}";
            const timer = {{ $timer }};
            let countdown = timer;

            const timerEl = document.getElementById('timer');
            const interval = setInterval(() => {
                if (timerEl) timerEl.innerText = countdown;
                countdown--;

                if (countdown < 0) {
                    clearInterval(interval);
                    window.location.href = redirectUrl;
                }
            }, 1000);
        });
    </script>
</head>

<body>
    <div class="message-box">
        <div class="success-container">
            <img src="https://scontent-lcy1-1.xx.fbcdn.net/v/t1.6435-9/31301640_2114242505489348_3921532491046846464_n.png?_nc_cat=104&ccb=1-3&_nc_sid=973b4a&_nc_ohc=pfOalMq8BzUAX-k-rhY&_nc_ht=scontent-lcy1-1.xx&oh=3af014dd12fa6e3d1816a3425a80e516&oe=609BE04A"
                alt="Wego Stores Logo">

            <h3><i>Paymob</i></h3>
            <hr>
            <h1>Thank you for your order</h1>

            <div class="confirm-green-box">
                <p><strong>Order received successfully!</strong></p>
            </div>

            <small>Total Amount: {{ $totalAmount }}</small>
        </div>
    </div>

    <section class="redirect-section container">
        <h1>{{ $message }}</h1>
        <p>You will be redirected in <span id="timer">{{ $timer }}</span> seconds...</p>
    </section>
</body>

</html>
