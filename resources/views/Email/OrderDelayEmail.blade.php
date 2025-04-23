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
          <p>Hello,</p>
          <p>We wanted to let you know that your order has been slightly delayed and will be processed within the next <strong>3 minutes</strong>.</p>
          <p style="margin-top:30px;">Order ,<br /><strong>#{{$data['id']}}</strong></p>
        </td>
      </tr>
      <tr>
        <td style="padding:15px; background-color:#eeeeee; text-align:center; font-size:12px; color:#666;">
          This is an automated message. Please do not reply.
        </td>
      </tr>
    </table>
  </body>
</html>
