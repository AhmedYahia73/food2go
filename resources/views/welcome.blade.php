<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Socket</title>
</head>
<body>
   @vite('resources/js/app.js') 
</body>

<script>
    setTimeout(()=>{

    window.Echo.channel('new_order')
    .listen('OrderEvent', (e) => {
        console.log(e);
        
    })
    }, 2000)
</script>
</html>