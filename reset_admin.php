<?php
/**
 * reset_admin.php - Admin Password Reset (1-Click Fix)
 * 
 * Agar admin login nahi ho raha, to bas is file ko browser mein open karein:
 * http://localhost/project/reset_admin.php
 * 
 * Yeh script:
 * 1. Agar admin user nahi hai to create karega
 * 2. Agar hai to uska password admin123 set kar dega with CORRECT bcrypt hash
 * 
 * IMPORTANT: Kaam ho jane ke baad is file ko DELETE kar dena!
 */

require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Admin Password Reset</title>
<style>body{font-family:Arial,sans-serif;background:#f0f4f8;margin:0;padding:40px;}
.box{max-width:600px;margin:0 auto;background:white;padding:35px;border-radius:15px;box-shadow:0 4px 20px rgba(0,0,0,.1);}
h1{color:#0d6efd;margin-top:0;}.ok{background:#d1e7dd;color:#0f5132;padding:15px;border-radius:8px;margin:15px 0;}
.err{background:#f8d7da;color:#842029;padding:15px;border-radius:8px;margin:15px 0;}
.info{background:#cfe2ff;color:#084298;padding:15px;border-radius:8px;margin:15px 0;}
code{background:#eee;padding:3px 8px;border-radius:4px;}
.btn{display:inline-block;background:#0d6efd;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;font-weight:bold;margin-top:15px;}
.warn{background:#fff3cd;color:#856404;padding:12px;border-radius:8px;margin-top:20px;font-size:14px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
td,th{border:1px solid #dee2e6;padding:10px;text-align:left;}
th{background:#f8f9fa;}</style></head><body><div class='box'>";

echo "<h1>🔑 Admin Password Reset</h1>";
echo "<hr>";

// -------- Step 1: Check admin email exists --------
echo "<h3>📋 Step 1: Admin User Check</h3>";
$admin_email = 'admin@hospital.com';
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$admin_email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "<div class='ok'>✅ Admin user mil gaya: <strong>{$admin['name']}</strong> (ID: {$admin['id']}, Role: {$admin['role']})</div>";
    $admin_id = $admin['id'];
    $needs_insert = false;
} else {
    echo "<div class='info'>⚠️ Admin user nahi mila. Naya create kar raha hoon...</div>";
    $needs_insert = true;
}

// -------- Step 2: Set correct password --------
echo "<h3>🔐 Step 2: Password Reset</h3>";
$new_password = 'admin123';
$correct_hash = password_hash($new_password, PASSWORD_DEFAULT);
echo "<p>Setting password: <code>$new_password</code></p>";
echo "<p>Generated hash: <code style='font-size:12px;word-break:break-all;'>$correct_hash</code></p>";

try {
    if ($needs_insert) {
        // Create fresh admin user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, gender, age, address, role) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'admin')");
        $ok = $stmt->execute(['Admin User', $admin_email, $correct_hash, '9876543210', 'Male', 35, 'Hospital Street, Medical City']);
        $admin_id = $conn->lastInsertId();
        $action_word = 'CREATED';
    } else {
        // Update existing admin password + ensure role=admin
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?");
        $ok = $stmt->execute([$correct_hash, $admin_id]);
        $action_word = 'UPDATED';
    }

    if ($ok) {
        echo "<div class='ok'>✅ Admin password successfully $action_word!</div>";
    } else {
        echo "<div class='err'>❌ Database error: Update/Insert fail ho gaya.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='err'>❌ Error: " . $e->getMessage() . "</div>";
}

// -------- Step 3: LIVE VERIFICATION --------
echo "<h3>✅ Step 3: Verification Test</h3>";
$stmt = $conn->prepare("SELECT password FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$admin_email]);
$db_hash = $stmt->fetchColumn();

if ($db_hash) {
    $verify_result = password_verify($new_password, $db_hash);
    if ($verify_result) {
        echo "<div class='ok'>🎉 TEST PASSED! <code>password_verify('admin123', hash)</code> = <strong>TRUE</strong></div>";
        echo "<p>Ab admin bilkul login ho jayega!</p>";
    } else {
        echo "<div class='err'>❌ TEST FAILED! Password verify nahi ho raha.</div>";
    }
}

// -------- Login Credentials Box --------
echo "<hr>";
echo "<h3>🎯 Admin Login Credentials</h3>";
echo "<table>
<tr><th>Email</th><td><code>$admin_email</code></td></tr>
<tr><th>Password</th><td><code>$new_password</code></td></tr>
<tr><th>Role</th><td>👨‍💼 Administrator</td></tr>
</table>";
echo "<a href='login.php' class='btn'>👉 Login Page Par Jaayein</a>";

// -------- Also reset sample patients (optional help) --------
echo "<hr>";
echo "<h3>🩺 Sample Patients bhi Reset kar raha hoon</h3>";
$patients = [
    'rajesh@email.com' => ['Rajesh Kumar', 'patient123'],
    'priya@email.com'  => ['Priya Sharma', 'patient123'],
    'amit@email.com'   => ['Amit Verma', 'patient123'],
];
foreach ($patients as $email => $data) {
    list($name, $pwd) = $data;
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        $stmt = $conn->prepare("UPDATE users SET password = ?, role='patient' WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "<div class='ok' style='padding:8px 12px;margin:5px 0;font-size:14px;'>✅ Updated: $email / $pwd</div>";
    } else {
        echo "<div class='info' style='padding:8px 12px;margin:5px 0;font-size:14px;'>ℹ️ Skip: $email (DB mein nahi hai - register se bana sakte ho)</div>";
    }
}

echo "<div class='warn'>⚠️ SECURITY NOTE: Project poora set up ho jane ke baad is file (<code>reset_admin.php</code>) ko DELETE kar dena taaki koi aur admin password reset na kar sake!</div>";

echo "</div></body></html>";
?>
