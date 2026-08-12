<?php
/**
 * fix_passwords.php - Password Fix Utility
 * 
 * Agar login nahi ho raha to yeh file browser mein open karein:
 * http://localhost/project/fix_passwords.php
 * 
 * Yeh script sab users ke passwords ko sahi hash se update kar dega.
 */

require_once 'config.php';

echo "<h2>🔧 Password Fix Tool</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}
      .box{background:white;padding:25px;border-radius:10px;margin-bottom:15px;}
      .ok{color:green;}.err{color:red;}.info{color:blue;}</style>";

// ---------- STEP 1: Check DB connection ----------
echo "<div class='box'>";
echo "<h3>Step 1: Database Check</h3>";
try {
    $conn->query("SELECT 1");
    echo "<p class='ok'>✅ Database Connection: OK</p>";
} catch (Exception $e) {
    echo "<p class='err'>❌ Database Connection Failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>👉 database.sql file ko phpMyAdmin se import karein.</p>";
    echo "</div>";
    exit;
}
echo "</div>";

// ---------- STEP 2: Check tables exist ----------
echo "<div class='box'>";
echo "<h3>Step 2: Tables Check</h3>";
$required_tables = ['users', 'doctors', 'appointments'];
$all_ok = true;
foreach ($required_tables as $t) {
    $check = $conn->query("SHOW TABLES LIKE '$t'");
    if ($check->rowCount() > 0) {
        echo "<p class='ok'>✅ Table `$t`: Found</p>";
    } else {
        echo "<p class='err'>❌ Table `$t`: NOT FOUND</p>";
        $all_ok = false;
    }
}
if (!$all_ok) {
    echo "<p class='info'>👉 database.sql ko import karein - saari tables create ho jayengi.</p>";
    echo "</div>";
    exit;
}
echo "</div>";

// ---------- STEP 3: Check if users exist ----------
echo "<div class='box'>";
echo "<h3>Step 3: Users Check</h3>";
$stmt = $conn->query("SELECT id, email, name, role FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($users) == 0) {
    echo "<p class='err'>❌ Koi bhi user nahi mila. Sample users insert kar rahe hain...</p>";
    $sql = file_get_contents(__DIR__ . '/database.sql');
    echo "<p class='info'>👉 phpMyAdmin mein jake database.sql import karein.</p>";
    echo "</div>";
    exit;
}
echo "<p class='ok'>✅ Total Users: " . count($users) . "</p>";
echo "<ul>";
foreach ($users as $u) {
    echo "<li><strong>{$u['name']}</strong> ({$u['role']}) - {$u['email']}</li>";
}
echo "</ul></div>";

// ---------- STEP 4: Update passwords to correct hashes ----------
echo "<div class='box'>";
echo "<h3>Step 4: Password Reset (Important!)</h3>";

$password_map = [
    // Admin
    'admin@hospital.com' => 'admin123',
    // Sample patients
    'rajesh@email.com' => 'patient123',
    'priya@email.com'  => 'patient123',
    'amit@email.com'   => 'patient123',
];

$updated_count = 0;
foreach ($password_map as $email => $plain_password) {
    $hash = password_hash($plain_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hash, $email]);
    if ($result && $stmt->rowCount() > 0) {
        echo "<p class='ok'>✅ Password UPDATED: $email → <code>$plain_password</code></p>";
        $updated_count++;
    } else {
        echo "<p class='info'>ℹ️ Skipped (email not in DB or already updated): $email</p>";
    }
}

if ($updated_count > 0) {
    echo "<p class='ok'><strong>✓ Sabhi passwords reset ho gaye!</strong></p>";
}
echo "</div>";

// ---------- STEP 5: Verify passwords work ----------
echo "<div class='box'>";
echo "<h3>Step 5: Login Verification Test</h3>";
foreach ($password_map as $email => $plain) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        if (password_verify($plain, $row['password'])) {
            echo "<p class='ok'>✅ TEST PASSED: $email / $plain — Ab isse login ho jayega!</p>";
        } else {
            echo "<p class='err'>❌ TEST FAILED: $email — Hash match nahi kar raha.</p>";
        }
    }
}
echo "</div>";

// ---------- Final instructions ----------
echo "<div class='box'>";
echo "<h3>🎯 Ab Login Karein!</h3>";
echo "<h4>Admin Login:</h4>";
echo "<ul><li>Email: <strong>admin@hospital.com</strong></li><li>Password: <strong>admin123</strong></li></ul>";
echo "<h4>Patient Login:</h4>";
echo "<ul><li>Email: <strong>rajesh@email.com</strong></li><li>Password: <strong>patient123</strong></li></ul>";
echo "<p><a href='login.php' style='background:#0d6efd;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;'>👉 Login Page Par Jaayein</a></p>";
echo "<p class='info'>Note: Iss file ko baad mein delete kar dena security ke liye.</p>";
echo "</div>";
?>
