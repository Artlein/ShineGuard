<?php
/**
 * SHINEGUARD CORPORATE EMAIL TEMPLATE: PASSWORD RECOVERY
 * This template follows enterprise design standards with responsive glassmorphism.
 */

function getPasswordResetTemplate($user_name, $reset_link, $expires_in = '60 minutes') {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset Request</title>
        <style>
            body { 
                margin: 0; padding: 0; background-color: #f8fafc; 
                font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            }
            .wrapper { width: 100%; table-layout: fixed; padding: 40px 0; background-color: #0f172a; }
            .container { 
                max-width: 600px; margin: 0 auto; background: #ffffff; 
                border-radius: 16px; overflow: hidden; 
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
            }
            .header { 
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
                padding: 40px 20px; text-align: center; border-bottom: 2px solid #3b82f6;
            }
            .logo { height: 60px; margin-bottom: 10px; }
            .content { padding: 40px; color: #1e293b; line-height: 1.6; }
            h1 { font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 0; }
            p { font-size: 16px; color: #475569; margin-bottom: 24px; }
            .btn-wrapper { text-align: center; padding: 20px 0; }
            .button { 
                background-color: #3b82f6; color: #ffffff !important; 
                padding: 16px 32px; border-radius: 12px; 
                text-decoration: none; font-weight: 700; font-size: 16px;
                display: inline-block; box-shadow: 0 4px 6px -1px rgba(59,130,246,0.5);
            }
            .footer { 
                background-color: #f8fafc; padding: 30px; 
                text-align: center; font-size: 12px; color: #94a3b8; 
                border-top: 1px solid #e2e8f0;
            }
            .warning-box {
                background: #fff7ed; border: 1px solid #fed7aa; 
                border-radius: 8px; padding: 15px; margin-top: 30px;
                color: #9a3412; font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="container">
                <div class="header">
                    <img src="https://i.ibb.co/vzG7P9y/Shine-Guard3.png" alt="ShineGuard Logo" class="logo">
                    <div style="color: #60a5fa; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">Infrastructure Security</div>
                </div>
                
                <div class="content">
                    <h1>Security Alert: Password Reset</h1>
                    <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
                    <p>A request was received to reset the password for your ShineGuard account. To proceed with the recovery, please click the secure link below:</p>
                    
                    <div class="btn-wrapper">
                        <a href="' . $reset_link . '" class="button">🔒 Reset My Password</a>
                    </div>
                    
                    <p style="font-size: 14px; color: #94a3b8; text-align: center;">This unique link will expire in <strong>' . $expires_in . '</strong> for your protection.</p>
                    
                    <div class="warning-box">
                        <strong>🛡️ Didnt request this?</strong><br>
                        If you did not initiate this request, your account is still secure. No action is needed, but we recommend checking your recent activity logs.
                    </div>
                </div>
                
                <div class="footer">
                    <strong>Barangay Hulo Smart Infrastructure Center</strong><br>
                    Barangay Hulo, Mandaluyong City, Philippines<br>
                    © 2025 ShineGuard. All Rights Reserved.
                </div>
            </div>
        </div>
    </body>
    </html>';
}
