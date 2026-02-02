<?php
session_start();
include "db.php";
require 'admin_check.php';

/* ---------------------------
   FETCH ALL USERS
---------------------------- */
$result = $conn->query("
    SELECT user_id, username, email, role, status, created_at 
    FROM users 
    ORDER BY user_id ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:#f4f6f9;
            margin:0;
            padding:20px;
        }
        h1 { margin-bottom:20px; }
        .nav { margin-bottom:20px; }
        .nav a { margin-right:15px; text-decoration:none; font-weight:bold; color:#2563eb; }

        /* Alerts */
        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-weight:bold; }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-error { background:#fee2e2; color:#991b1b; }

        /* Card container */
        .card {
            background:#fff;
            padding:20px;
            border-radius:8px;
            box-shadow:0 2px 6px rgba(0,0,0,.1);
        }

        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
        th { background:#f9fafb; font-weight:600; }

        .badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-user { background:#e0f2fe; color:#0369a1; }
        .badge-admin { background:#ede9fe; color:#6d28d9; }
        .badge-active { background:#dcfce7; color:#166534; }
        .badge-blocked { background:#fee2e2; color:#991b1b; }

        .btn { padding:6px 12px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:bold; }
        .btn-block { background:#fee2e2; color:#991b1b; }
        .btn-unblock { background:#dcfce7; color:#166534; }
        .btn-disabled { color:#9ca3af; cursor:not-allowed; }
    </style>
</head>
<body>

<h1>Manage Users 👥</h1>

<div class="nav">
    <a href="admin_dashboard.php">← Dashboard</a>
    <a href="logout.php">Logout</a>
</div>

<!-- ---------------------------
     SESSION MESSAGES
---------------------------- -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card">
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <span class="badge <?= $row['role']==='admin' ? 'badge-admin' : 'badge-user' ?>">
                    <?= ucfirst($row['role']) ?>
                </span>
            </td>
            <td>
                <span class="badge <?= $row['status']==='active' ? 'badge-active' : 'badge-blocked' ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </td>
            <td>
                <?php if ($row['role'] === 'admin'): ?>
                    <span class="btn btn-disabled">—</span>
                <?php else: ?>
                    <?php if ($row['status'] === 'active'): ?>
                        <a class="btn btn-block"
                           href="toggle_user.php?id=<?= $row['user_id'] ?>&action=block"
                           onclick="return confirm('Block this user?')">
                           🚫 Block
                        </a>
                    <?php else: ?>
                        <a class="btn btn-unblock"
                           href="toggle_user.php?id=<?= $row['user_id'] ?>&action=unblock"
                           onclick="return confirm('Unblock this user?')">
                           ✅ Unblock
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">No users found.</td>
        </tr>
    <?php endif; ?>
</table>
</div>

</body>
</html>
