<!DOCTYPE html>
<html>

<head>
    <title>Expired Blood Count</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f2f2f2; margin: 0; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        <div style="border-bottom: 1px solid #eee; padding-bottom: 10px; text-align: center;">
            <img src="{{ $message->embed(public_path('logo/Lifelink-logo.png')) }}" alt="Lifelink Logo"
                style="border: 0; display: block; outline: none; text-decoration: none; height: auto; width: 150px; margin: 20px auto;" />
        </div>
        <div style="padding: 20px;">
            <p style="font-size: 18px; color: #333;">Hi,</p>
            <p style="font-size: 16px; color: #333;">As of {{ \Carbon\Carbon::now()->format('F j, Y, g:i a') }} below are the count of expired blood bags:</p>
            <div
                style="background: #784242; color: #ffffff; padding: 15px; text-align: center; border-radius: 4px; margin: 20px 0;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 600;">{{ $count }}</h2>
                <p style="margin: 0; font-size: 14px; color: #ffdddd;">Expired Blood Bag Count</p>
            </div>
            <p style="font-size: 16px; color: #333;">Best regards,<br />Life Link</p>
        </div>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />
        <div style="text-align: center; color: #aaa; font-size: 14px; font-weight: 300;">
            <p>Philippine Red Cross Valenzuela City Chapter</p>
            <p>ALERT Center Compound, Valenzuela City</p>
            <p>Philippines</p>
        </div>
    </div>
</body>


</html>
