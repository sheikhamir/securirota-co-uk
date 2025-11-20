<?php
session_start();

echo "🔍 Session Status Check\n";
echo "=======================\n\n";

echo "Session Data:\n";
echo "- user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "- user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
echo "- company_id: " . ($_SESSION['company_id'] ?? 'NOT SET') . "\n\n";

// Check if user needs to login
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    echo "❌ Not logged in as super admin\n";
    echo "Please login at: http://localhost/rota/login.php\n";
    echo "Username: root\n";
    echo "Password: admin123\n";
} else {
    echo "✅ Logged in as super admin\n";
}
?>
