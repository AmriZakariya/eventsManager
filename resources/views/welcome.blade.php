<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Manager API - Alpha</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #1A2035; /* Matches your AppTheme.navy */
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        /* Subtle background radial gradient */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 48px 40px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            max-width: 400px;
            width: 85%;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(16, 185, 129, 0.1);
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin-bottom: 24px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10B981; /* Matches your AppTheme.green */
            border-radius: 50%;
            margin-right: 10px;
            box-shadow: 0 0 10px #10B981;
            animation: pulse 2s infinite;
        }

        .status-text {
            color: #10B981;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        p {
            margin: 0;
            color: #94A3B8;
            font-size: 15px;
            line-height: 1.6;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            margin: 32px 0;
        }

        .footer {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.3);
            font-family: monospace;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="status-badge">
        <div class="pulse-dot"></div>
        <span class="status-text">Alpha Server Online</span>
    </div>

    <h1>Events Manager API</h1>
    <p>This environment is currently restricted to authorized testing and development traffic.</p>

    <div class="divider"></div>

    <div class="footer">
        v1.0.0-alpha &bull; {{ app()->version() }}
    </div>
</div>
</body>
</html>
