<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - HiWash</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            width: 100% !important;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f3f4f6;
            padding: 20px 0;
        }
        .main {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
            color: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #E30613;
            padding: 50px 20px 10px; /* Padding bawah dikurangi agar menyatu dengan gelombang */
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.04em;
            text-transform: uppercase;
        }
        .header h1 span {
            color: #FFE800;
        }
        .header p {
            color: #ffffff;
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
        }
        .wave-area {
            background-color: #ffffff; /* Latar belakang diset putih karena bawah SVG adalah putih */
            line-height: 0;
            font-size: 0;
            padding: 0;
        }
        .wave-img {
            display: block;
            width: 100%;
            height: auto;
            border: none;
        }
        .content {
            padding: 10px 40px 40px; /* Padding atas dikurangi agar menyatu */
            line-height: 1.6;
        }
        .content h2 {
            margin-top: 0;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .content p {
            margin-bottom: 20px;
            color: #475569;
            font-size: 15px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background-color: #E30613;
            color: #ffffff !important;
            padding: 16px 36px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            padding: 25px 20px;
            font-size: 12px;
            color: #94a3b8;
        }
        .trouble {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
            color: #64748b;
            word-break: break-all;
            border: 1px solid #e2e8f0;
        }
        .trouble a {
            color: #E30613;
            text-decoration: none;
            font-weight: 600;
        }
        .brand-color {
            color: #E30613;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" cellpadding="0" cellspacing="0" width="600" style="width: 100%; max-width: 600px;">
            <tr>
                <td class="header" bgcolor="#E30613" style="background-color: #E30613;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 800; text-transform: uppercase;">Hi<span style="color: #FFE800;">Wash</span></h1>
                    <p style="color: #ffffff; margin: 5px 0 0; font-size: 14px; font-weight: 600; opacity: 0.9;">Laundry Express Samarinda</p>
                </td>
            </tr>
            <tr>
                <td class="wave-area" bgcolor="#ffffff" style="background-color: #ffffff;">
                    <img class="wave-img" src="{{ asset('wave.png') }}" width="600" height="auto" alt="" style="display: block; width: 100%; height: auto; border: none;">
                </td>
            </tr>
            <tr>
                <td class="content">
                    <h2>Halo, {{ $name }}!</h2>
                    <p>Selamat datang di <span class="brand-color">HiWash</span>! Kami sangat senang Anda memilih kami untuk mengelola kebutuhan laundry Anda.</p>
                    <p>Silakan konfirmasi alamat email Anda dengan menekan tombol di bawah ini:</p>

                    <div class="button-container">
                        <a href="{{ $url }}" class="button">Konfirmasi Alamat Email</a>
                    </div>

                    <p>Jika Anda tidak merasa melakukan pendaftaran ini, abaikan email ini.</p>

                    <p>Terima kasih,<br><strong>Tim HiWash</strong></p>

                    <div class="trouble">
                        <strong>Link tidak berfungsi?</strong><br>
                        Salin tautan berikut ke browser Anda:
                        <br><br>
                        <a href="{{ $url }}">{{ $url }}</a>
                    </div>
                </td>
            </tr>
        </table>
        <div class="footer">
            &copy; {{ date('Y') }} <strong>HiWash</strong>. All rights reserved.
        </div>
    </div>
</body>
</html>
