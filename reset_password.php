<?php
include "db.php";

$message = "";
$success = false;

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    die("Invalid or missing token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $sql = "SELECT donor_id, token_expires, used 
                FROM donor_forget 
                WHERE reset_token = ? 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "Invalid reset token.";
        } else {
            $row = $result->fetch_assoc();

            if ((int)$row['used'] === 1) {
                $message = "This reset link has already been used.";
            } elseif (strtotime($row['token_expires']) < time()) {
                $message = "This reset link has expired.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $donor_id = $row['donor_id'];

                $updatePass = $conn->prepare("UPDATE donor_authentication SET password = ? WHERE donor_id = ?");
                $updatePass->bind_param("si", $hashed_password, $donor_id);

                if ($updatePass->execute()) {
                    $markUsed = $conn->prepare("UPDATE donor_forget SET used = 1 WHERE reset_token = ?");
                    $markUsed->bind_param("s", $token);
                    $markUsed->execute();
                    $markUsed->close();

                    $success = true;
                    $message = "Password reset successful. You may now go back to the app and log in.";
                } else {
                    $message = "Failed to update password.";
                }

                $updatePass->close();
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password - eDonate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 30px;
        }
        .box {
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        h2 {
            color: #850000;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #850000;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .message {
            margin-bottom: 15px;
            color: <?php echo $success ? 'green' : 'red'; ?>;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reset Password</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label>New Password</label>
                <input type="password" name="new_password" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>