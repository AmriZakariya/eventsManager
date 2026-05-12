<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Privacy Policy | HYGIE-CLEAN EXPO</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <style>

        :root {
            --bg-primary: #020617;
            --bg-secondary: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.75);

            --accent: #02CA67;
            --accent-blue: #00A1EC;

            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;

            --border: rgba(255,255,255,0.08);
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(2,202,103,0.15), transparent 30%),
                radial-gradient(circle at top right, rgba(0,161,236,0.12), transparent 30%),
                var(--bg-primary);

            color: var(--text-primary);
            line-height: 1.7;
            overflow-x: hidden;
        }

        .hero {
            padding: 90px 20px 70px;
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            background: rgba(2,202,103,0.12);

            border: 1px solid rgba(2,202,103,0.25);

            color: var(--accent);

            padding: 10px 18px;

            border-radius: 999px;

            font-size: 14px;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 18px;

            background: linear-gradient(
                to right,
                #ffffff,
                #02CA67
            );

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-secondary);
            max-width: 700px;
            margin: auto;
            font-size: 18px;
        }

        .updated {
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }

        .container {
            max-width: 950px;
            margin: auto;
            padding: 0 20px 80px;
        }

        .card {

            background: var(--card-bg);

            backdrop-filter: blur(14px);

            border: 1px solid var(--border);

            border-radius: 30px;

            padding: 50px;

            box-shadow:
                0 10px 40px rgba(0,0,0,0.35);

        }

        .section {
            display: flex;
            gap: 22px;
            margin-bottom: 45px;
            align-items: flex-start;
        }

        .icon {

            width: 58px;
            height: 58px;

            min-width: 58px;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    rgba(2,202,103,0.20),
                    rgba(0,161,236,0.15)
                );

            border: 1px solid rgba(255,255,255,0.08);

            display:flex;
            align-items:center;
            justify-content:center;

            transition: .3s ease;
        }

        .section:hover .icon {
            transform: translateY(-4px);
        }

        .icon span {
            color: var(--accent);
            font-size: 28px;
        }

        .content h2 {
            font-size: 20px;
            margin-bottom: 12px;
            color: white;
        }

        .content p,
        .content li {
            color: var(--text-secondary);
            font-size: 15px;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 10px;
        }

        .highlight {
            color: var(--accent);
            font-weight: 600;
        }

        .contact-box {

            margin-top: 50px;

            padding: 24px;

            border-radius: 20px;

            background:
                linear-gradient(
                    135deg,
                    rgba(2,202,103,0.12),
                    rgba(0,161,236,0.10)
                );

            border: 1px solid rgba(255,255,255,0.06);

        }

        .contact-box strong {
            color: white;
        }

        footer {

            margin-top: 60px;
            padding-top: 30px;

            border-top: 1px solid rgba(255,255,255,0.08);

            text-align:center;

            color: #64748b;
            font-size: 14px;
        }

        @media(max-width:768px){

            .hero h1{
                font-size:38px;
            }

            .hero p{
                font-size:16px;
            }

            .card{
                padding:28px;
                border-radius:24px;
            }

            .section{
                flex-direction:column;
            }

            .icon{
                width:52px;
                height:52px;
            }

        }

    </style>
</head>
<body>

<section class="hero">

    <div class="hero-badge">
        <span class="material-icons-round">verified_user</span>
        Privacy & Security
    </div>

    <h1>Privacy Policy</h1>

    <p>
        Your privacy and data security are important to us.
        This policy explains how HYGIE-CLEAN EXPO handles user data and device permissions.
    </p>

    <div class="updated">
        Last updated: {{ date('d F Y') }}
    </div>

</section>

<div class="container">

    <div class="card">

        <div class="section">

            <div class="icon">
                <span class="material-icons-round">info</span>
            </div>

            <div class="content">

                <h2>Introduction</h2>

                <p>
                    HYGIE-CLEAN EXPO is committed to protecting your personal information
                    and ensuring transparency regarding how your data is used.
                </p>

            </div>

        </div>

        <div class="section">

            <div class="icon">
                <span class="material-icons-round">camera_alt</span>
            </div>

            <div class="content">

                <h2>Camera Permission</h2>

                <p>
                    Our application may use the device camera for features such as:
                </p>

                <ul>
                    <li>QR code scanning</li>
                    <li>Barcode scanning</li>
                    <li>Event check-in features</li>
                </ul>

                <p>
                    <span class="highlight">
                        We do not store or share camera images or videos.
                    </span>
                </p>

            </div>

        </div>

        <div class="section">

            <div class="icon">
                <span class="material-icons-round">security</span>
            </div>

            <div class="content">

                <h2>Data Protection</h2>

                <p>
                    We implement security measures to protect your information against
                    unauthorized access, alteration, disclosure, or destruction.
                </p>

            </div>

        </div>

        <div class="section">

            <div class="icon">
                <span class="material-icons-round">devices</span>
            </div>

            <div class="content">

                <h2>Device Permissions</h2>

                <p>
                    Some device permissions may be requested strictly for application functionality
                    and user experience improvement.
                </p>

            </div>

        </div>

        <div class="section">

            <div class="icon">
                <span class="material-icons-round">gavel</span>
            </div>

            <div class="content">

                <h2>User Rights</h2>

                <p>
                    Users may request access, correction, or deletion of their personal information
                    by contacting us directly.
                </p>

            </div>

        </div>

        <div class="contact-box">

            <h2 style="margin-bottom:12px;">Contact Us</h2>

            <p>
                If you have questions regarding this Privacy Policy,
                please contact us:
            </p>

            <br>

            <strong>
                support@hygiecleanexpo.com
            </strong>

        </div>

        <footer>
            © {{ date('Y') }} HYGIE-CLEAN EXPO — All Rights Reserved
        </footer>

    </div>

</div>

</body>
</html>
