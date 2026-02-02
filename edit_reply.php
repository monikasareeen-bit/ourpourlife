<?php
session_start();
require 'db.php';
include 'header.php';

$error = '';

/* AUTH CHECK */
if (empty($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'user';

$reply_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reply_id <= 0) {
    $error = "Invalid reply ID.";
}

/* FETCH REPLY WITH TIME CHECK */
$reply = null;

if (!$error) {
    $stmt = $conn->prepare("
        SELECT reply_id, reply_text, blog_id, user_id, created_at,
        TIMESTAMPDIFF(HOUR, created_at, NOW()) AS hours_since_creation
        FROM replies
        WHERE reply_id = ?
    ");
    $stmt->bind_param("i", $reply_id);
    $stmt->execute();
    $reply = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reply) {
        $error = "Reply not found.";
    }
}

/* 🔐 ACCESS CONTROL WITH 24-HOUR RESTRICTION */
if (!$error) {
    if ($role === 'admin') {
        // Admin can always edit
    } elseif ($reply['user_id'] == $user_id) {
        // Check if within 24 hours
        if ($reply['hours_since_creation'] >= 24) {
            $error = "You can only edit replies within 24 hours of creation. This reply was created " . 
                     $reply['hours_since_creation'] . " hours ago.";
        }
    } else {
        $error = "You are not allowed to edit this reply.";
    }
}

/* UPDATE REPLY */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $reply_text = trim($_POST['reply'] ?? '');

    if ($reply_text === '') {
        $error = "Reply cannot be empty.";
    } else {
        $stmt = $conn->prepare("
            UPDATE replies
            SET reply_text = ?
            WHERE reply_id = ?
        ");
        $stmt->bind_param("si", $reply_text, $reply_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Reply updated successfully!";
            header("Location: blog.php?id=" . $reply['blog_id']);
            exit;
        } else {
            $error = "Failed to update reply. Please try again.";
        }
        $stmt->close();
    }
}
?>

<style>
.page-edit-reply {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.edit-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 40px;
}

.page-title {
    font-size: 2.2rem;
    color: #2d3748;
    margin-bottom: 10px;
    text-align: center;
}

.page-subtitle {
    text-align: center;
    color: #718096;
    margin-bottom: 30px;
    font-size: 1rem;
}

.error-message {
    background-color: #fff5f5;
    color: #c53030;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #e53e3e;
    margin-bottom: 20px;
}

.warning-message {
    background-color: #fffaf0;
    color: #dd6b20;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #f6ad55;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
    resize: vertical;
    min-height: 150px;
    line-height: 1.6;
}

.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    flex: 1;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background-color: #f7fafc;
    color: #4a5568;
    border: 2px solid #cbd5e0;
    flex: 1;
}

.btn-secondary:hover {
    background-color: #edf2f7;
    border-color: #a0aec0;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #4a5568;
    text-decoration: none;
    font-size: 0.95rem;
    margin-top: 20px;
    padding: 8px 0;
    transition: color 0.2s;
}

.back-link:hover {
    color: #667eea;
}

.error-actions {
    text-align: center;
    margin-top: 20px;
}

/* Responsive design */
@media (max-width: 768px) {
    .edit-container {
        padding: 25px;
    }
    
    .page-title {
        font-size: 1.8rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<main class="page-edit-reply">
    <div class="edit-container">
        
        <h1 class="page-title">✏️ Edit Reply</h1>
        <p class="page-subtitle">Update your reply content</p>

        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>

            <div class="error-actions">
                <?php if ($reply): ?>
                    <a href="blog.php?id=<?= $reply['blog_id'] ?>" class="btn btn-secondary">← Back to Blog</a>
                <?php else: ?>
                    <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <?php if ($reply['hours_since_creation'] > 0 && $reply['hours_since_creation'] < 24): ?>
                <div class="warning-message">
                    ⏰ Time remaining to edit: <?= 24 - $reply['hours_since_creation'] ?> hours
                </div>
            <?php endif; ?>

            <form method="post">

                <div class="form-group">
                    <label for="reply">Reply Content</label>
                    <textarea 
                        id="reply" 
                        name="reply" 
                        required
                        placeholder="Write your reply here..."
                    ><?= htmlspecialchars($reply['reply_text']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Save Changes
                    </button>
                    <a href="blog.php?id=<?= $reply['blog_id'] ?>" class="btn btn-secondary">
                        ✖️ Cancel
                    </a>
                </div>

                <div style="text-align: center;">
                    <a href="blog.php?id=<?= $reply['blog_id'] ?>" class="back-link">
                        ← Back to Blog
                    </a>
                </div>

            </form>

        <?php endif; ?>

    </div>
</main>

<?php include 'footer.php'; ?>