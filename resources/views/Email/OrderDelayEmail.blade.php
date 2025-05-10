<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Order Delay Notification</title>
  </head>
  <body style="margin:0; padding:0; font-family:Arial, sans-serif; background-color:#f4f4f4;">
    <table align="center" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff; margin-top:20px; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <tr>
        <td style="padding:20px; background-color:#f44336; color:white; text-align:center;">
          <h2 style="margin:0;">Order Delay Notification</h2>
        </td>
      </tr>
      <tr>
        <td style="padding:20px; color:#333333;">
          <p>Hello, admin</p> 


          <p>Order #{{$data['id']}} is delayed
          Order placed from </strong>"{{ $data['date'] }}"</strong>
            </p> 
        </td>
      </tr>
      <tr>
        <td style="padding:15px; background-color:#eeeeee; text-align:center; font-size:12px; color:#666;">
                  
        "This email is automatically sent from your restaurant dashboard"
        Thank you for using Food2go
        </td>
      </tr>
    </table>
  </body>
</html>
