<!DOCTYPE html>
<html>
<head>
    <title>OTP Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f2f2f2;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        <div style="border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <a href="#" style="font-size: 1.4em; color: #00466a; text-decoration: none; font-weight: 600;">Life Link</a>
        </div>
        <p style="font-size: 16px;">Hi,</p>
        <p style="font-size: 16px;">Use the following OTP to complete your password update:</p>
        <div style="background: rgb(120, 66, 66); color: #ffffff; padding: 10px; text-align: center; border-radius: 4px;">
            <h2 style="margin: 0; font-size: 24px; font-weight: 600;">{{ $otp }}</h2>
            <p style="margin: 0; font-size: 14px; color: #ffdddd;">OTP is valid for 5 minutes</p>
        </div>
        <p style="font-size: 16px;">If you didn't request this OTP, please ignore this email.</p>
        <p style="font-size: 16px;">Best regards,<br />Life Link</p>
        <hr style="border: none; border-top: 1px solid #eee;" />
        <div style="float: right; color: #aaa; font-size: 14px; font-weight: 300;">
            <p>Philippine Red Cross Valenzuela City Chapter</p>
            <p> LERT Center Compound, Valenzuela City</p>
            <p>Philippines</p>
        </div>
    </div>
</body>
</html>
