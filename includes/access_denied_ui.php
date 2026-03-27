<?php
/**
 * access_denied_ui.php
 * Premium Access Restricted Page for ShineGuard
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0d1117;
            --bg-card: #161b22;
            --accent: #10b981;
            --accent-glow: rgba(16, 185, 129, 0.2);
            --text-hi: #f0f6fc;
            --text-muted: #8b949e;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-hi);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
        }

        /* Ambient Glow Background */
        .glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background: var(--accent);
            filter: blur(120px);
            opacity: 0.15;
            z-index: -1;
            pointer-events: none;
        }

        .icon-box {
            width: 100px;
            height: 100px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            position: relative;
        }

        .icon-box svg {
            width: 50px;
            height: 50px;
            color: var(--accent);
            filter: drop-shadow(0 0 8px var(--accent-glow));
        }

        /* Pulsing ring animation */
        .icon-box::after {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 35px;
            border: 2px solid var(--accent);
            opacity: 0;
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.9); opacity: 0; }
            50% { opacity: 0.3; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.03em;
            background: linear-gradient(to bottom right, #fff, #8b949e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--accent);
            color: #000;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
            background: #34d399;
        }

        .btn svg {
            width: 18px;
            height: 18px;
            stroke-width: 3;
        }

        .footer-note {
            margin-top: 32px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

    </style>
</head>
<body>
    <div class="glow"></div>
    <div class="container">
        <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1>Access Restricted</h1>
        <p>You don't have the necessary administrative privileges to view this page. If you believe this is an error, please contact your system administrator.</p>
        
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Return to Dashboard
        </a>

        <div class="footer-note">Security Level: Strict</div>
    </div>
</body>
</html>
<?php exit(); ?>
