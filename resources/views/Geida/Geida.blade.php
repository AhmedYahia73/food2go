<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إتمام الدفع</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
        #geidea-checkout {
            width: 100%;
            max-width: 500px;
            min-height: 400px;
        }
    </style>
</head>
<body>

    {{-- ✅ الـ Container اللي الـ HPP هيتعرض فيه --}}
    <div id="geidea-checkout"></div>

    <script src="{{ $hppScript }}"></script>
    <script>
        window.onload = function () {
            GeideaCheckout({
                sessionId:      "{{ $sessionId }}",
                merchantKey:    "{{ $merchantKey }}",
                containerId:    "geidea-checkout",  // ✅ ربطه بالـ div
                onSuccess: function (data) {
                    console.log("Payment Success", data);
                },
                onError: function (data) {
                    console.log("Payment Error", data);
                },
                onCancel: function () {
                    window.history.back();
                }
            });
        };
    </script>

</body>
</html>