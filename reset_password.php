<?php

require_once 'dbconnect.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($new_password)) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $password_hash, $username);
            
            if ($update_stmt->execute()) {
                $message = "Password updated successfully for user: $username<br>You can now login with your new password!";
                $messageType = 'success';
            } else {
                $message = 'Error updating password: ' . $conn->error;
                $messageType = 'error';
            }
            $update_stmt->close();
        } else {
            
            $full_name = $_POST['full_name'] ?? $username;
            $email = $_POST['email'] ?? $username . '@hulo.barangay.ph';
            $role = $_POST['role'] ?? 'Admin';
            
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $insert_stmt->bind_param("sssss", $username, $password_hash, $email, $full_name, $role);
            
            if ($insert_stmt->execute()) {
                $message = "New user created successfully!<br>Username: $username<br>Password: $new_password<br>Role: $role";
                $messageType = 'success';
            } else {
                $message = 'Error creating user: ' . $conn->error;
                $messageType = 'error';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$users_query = "SELECT username, email, role, created_at, last_login, is_active FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Shine Guard Hulo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1e293b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            color: #92400e;
        }
        .form-section {
            background: #f8fafc;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        h2 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #22c55e;
        }
        .btn {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d1fae5;
            border: 2px solid #6ee7b7;
            color: #065f46;
        }
        .message.error {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            color: #991b1b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.admin {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge.operator {
            background: #fef3c7;
            color: #92400e;
        }
        .badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        .quick-logins {
            background: #e0f2fe;
            border: 2px solid #7dd3fc;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .quick-logins h3 {
            color: #0c4a6e;
            margin-bottom: 10px;
        }
        code {
            background: #1e293b;
            color: #22c55e;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Password Reset Utility</h1>
        <p style="color: #64748b; margin-bottom: 20px;">Reset passwords or create new users for Shine Guard Hulo</p>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong><br>
            Delete this file (reset_password.php) after use! This tool should not be accessible in production.
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Reset Password or Create User</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required placeholder="Enter username">
                    <small style="color: #64748b;">If user exists, password will be reset. If not, new user will be created.</small>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password *</label>
                    <input type="password" id="password" name="password" required placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name (for new users)</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email (for new users)</label>
                    <input type="email" id="email" name="email" placeholder="Enter email">
                </div>
                
                <div class="form-group">
                    <label for="role">Role (for new users)</label>
                    <select id="role" name="role">
                        <option value="Admin">Admin</option>
                        <option value="Operator">Operator</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Reset Password / Create User</button>
            </form>
        </div>

        <div class="quick-logins">
            <h3>Quick Test Logins</h3>
            <p>You can quickly create these test accounts:</p>
            <ul style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">
                <li>Username: <code>admin</code> / Password: <code>admin123</code></li>
                <li>Username: <code>operator1</code> / Password: <code>pass123</code></li>
                <li>Username: <code>tech1</code> / Password: <code>tech123</code></li>
            </ul>
        </div>

        <div class="form-section">
            <h2>Existing Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo strtolower($user['role']); ?>"><?php echo $user['role']; ?></span></td>
                            <td><span class="badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b;">No users found. Database might not be set up correctly.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f1f5f9; border-radius: 10px;">
            <p style="color: #64748b; margin-bottom: 10px;">After fixing your password:</p>
            <a href="login.php" style="color: #22c55e; font-weight: 700; text-decoration: none; font-size: 18px;">
                → Go to Login Page
            </a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
