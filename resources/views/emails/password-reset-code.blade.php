<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <p>Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.</p>
        
        <a href="{{ $url }}" class="button">Reset Password</a>
        
        <p>Link ini akan kadaluarsa dalam {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} menit.</p>
        
        <p>Jika Anda tidak melakukan permintaan reset password, abaikan email ini.</p>
        
        <div class="footer">
            <p>Jika tombol di atas tidak berfungsi, copy dan paste URL berikut ke browser Anda:</p>
            <p>{{ $url }}</p>
        </div>
    </div>
</body>
</html>