<?php
include "db.php";
include 'admin_check.php';


/* 📊 STATISTICS */

// Total users
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")
                   ->fetch_assoc()['total'];

// Active users
$activeUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status='active'")
                    ->fetch_assoc()['total'];

// Blocked users
$blockedUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status='blocked'")
                     ->fetch_assoc()['total'];

// Total admins
$totalAdmins = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='admin'")
                    ->fetch_assoc()['total'];

// Total blog posts
$totalBlogs = $conn->query("SELECT COUNT(*) AS total FROM blogs")
                   ->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <a href="admin_replies.php">Manage Replies</a>

   <!-- <style>
        body { font-family: Arial; background:#f4f6f9; margin:0; padding:20px; }
        h1 { margin-bottom:20px; }
        .nav { margin-bottom:20px; }
        .nav a { margin-right:15px; text-decoration:none; font-weight:bold; color:#2563eb; }
        .dashboard { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:20px; }
        .card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.1); }
        .card h2 { margin:0; font-size:32px; color:#1f2937; }
        .card p { margin-top:5px; font-size:14px; color:#6b7280; }
    </style>-->
</head>
<body>

<h1>Welcome, Admin 👋</h1>

<div class="admin-nav">
    <a href="users.php">Manage Users</a>
    <a href="posts.php">Manage Blogs</a>
    <a href="logout.php">Logout</a>
</div>

<div class="admin-dashboard">
    <div class="admin-card"><h2><?= $totalUsers ?></h2><p>Total Users</p></div>
    <div class="admin-card"><h2><?= $activeUsers ?></h2><p>Active Users</p></div>
    <div class="admin-card"><h2><?= $blockedUsers ?></h2><p>Blocked Users</p></div>
    <div class="admin-card"><h2><?= $totalAdmins ?></h2><p>Total Admins</p></div>
    <div class="admin-card"><h2><?= $totalBlogs ?></h2><p>Total Blog Posts</p></div>
</div>

</body>
</html>
