<?php
session_start();
require 'db.php';
include 'header.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

/* SESSION ERROR / SUCCESS */
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$blog_id = (int) ($_GET['id'] ?? 0);
if ($blog_id <= 0) {
    $error = "Invalid blog ID";
}

/* FETCH BLOG */
$blog = null;
if (empty($error)) {
    $stmt = $conn->prepare("
      SELECT b.*, 
       t.topic_name, 
       t.emoji,
       u.username AS author,
       TIMESTAMPDIFF(HOUR, b.created_at, NOW()) AS hours_since_creation
FROM blogs b
LEFT JOIN topics t ON b.topic_id = t.topic_id
JOIN users u ON b.user_id = u.user_id
WHERE b.blog_id = ?

    ");
    $stmt->bind_param("i", $blog_id);
    $stmt->execute();
    $blog = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$blog) {
        $error = "Blog not found";
    }
}

/* STOP PAGE SAFELY IF ERROR */
if (!empty($error)) {
    ?>
    <main class="page-blog">
        <div class="posts-container">
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        </div>
    </main>
    <?php
    include 'footer.php';
    exit;
}

/* CHECK IF USER CAN EDIT/DELETE BLOG (24 hour rule) */
$can_edit_blog = false;
if ($is_logged_in) {
    if ($role === 'admin') {
        $can_edit_blog = true; // Admin can always edit
    } elseif ($blog['user_id'] == $user_id && $blog['hours_since_creation'] < 24) {
        $can_edit_blog = true; // Owner can edit within 24 hours
    }
}

/* BLOG LIKE COUNT */
$stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE target_id=? AND target_type='blog'");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$blog_like_count = $stmt->get_result()->fetch_row()[0];
$stmt->close();

/* FETCH REPLIES WITH TIME CHECK */
$replies = [];
$stmt = $conn->prepare("
    SELECT r.*,
       u.username AS user_name,
       TIMESTAMPDIFF(HOUR, r.created_at, NOW()) AS hours_since_creation,
       (SELECT COUNT(*) 
        FROM likes 
        WHERE target_id=r.reply_id AND target_type='reply') AS like_count
FROM replies r
JOIN users u ON r.user_id = u.user_id
WHERE r.blog_id=?
ORDER BY r.created_at ASC

");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $replies[] = $row;
}
$stmt->close();
?>

<style>
.page-blog {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.posts-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 40px;
    margin-bottom: 30px;
}

.post-card {
    margin-bottom: 30px;
}

.post-title {
    font-size: 2.5rem;
    color: #2d3748;
    margin-bottom: 15px;
    line-height: 1.3;
}

.success-message {
    background-color: #f0fff4;
    color: #38a169;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #48bb78;
    margin-bottom: 20px;
}

.error-message {
    background-color: #fff5f5;
    color: #c53030;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #e53e3e;
    margin: 20px 0;
}

.post-category {
    color: #718096;
    font-size: 0.95rem;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.post-content {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #4a5568;
    margin-bottom: 30px;
}

.post-media {
    max-width: 100%;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.blog-actions {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px 0;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    margin: 20px 0;
}

.actions-left,
.actions-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.blog-actions a,
.blog-actions .like-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

/* Like button */
.like-link {
    background-color: #fff5f5;
    color: #e53e3e;
    border: 1px solid #feb2b2;
}

.like-link:hover {
    background-color: #fed7d7;
    border-color: #fc8181;
}

/* Reply button */
.blog-actions a[onclick*="toggleReplyForm"] {
    background-color: #ebf8ff;
    color: #3182ce;
    border: 1px solid #90cdf4;
}

.blog-actions a[onclick*="toggleReplyForm"]:hover {
    background-color: #bee3f8;
    border-color: #63b3ed;
}

/* Edit button */
.btn-edit {
    background-color: #fffaf0;
    color: #dd6b20;
    border: 1px solid #fbd38d;
}

.btn-edit:hover {
    background-color: #feebc8;
    border-color: #f6ad55;
}

/* Delete button */
.btn-delete {
    background-color: #fff5f5;
    color: #c53030;
    border: 1px solid #fc8181;
}

.btn-delete:hover {
    background-color: #fed7d7;
    border-color: #f56565;
}

/* Disabled button */
.btn-disabled {
    background-color: #f7fafc;
    color: #a0aec0;
    border: 1px solid #e2e8f0;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-disabled:hover {
    background-color: #f7fafc;
    border-color: #e2e8f0;
    transform: none;
}

/* Back button */
.blog-actions a[href*="category.php"] {
    background-color: #f7fafc;
    color: #4a5568;
    border: 1px solid #cbd5e0;
}

.blog-actions a[href*="category.php"]:hover {
    background-color: #edf2f7;
    border-color: #a0aec0;
}

.reply-form-wrap {
    margin: 20px 0;
    padding: 20px;
    background-color: #f7fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.reply-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: vertical;
    margin-bottom: 10px;
}

.reply-form textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.reply-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    left: -9999px;
    opacity: 0;
}

.file-input-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background-color: #f7fafc;
    color: #4a5568;
    border: 2px dashed #cbd5e0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
    font-weight: 500;
    width: 100%;
    justify-content: center;
}

.file-input-label:hover {
    background-color: #edf2f7;
    border-color: #667eea;
    color: #667eea;
}

.file-input-label::before {
    content: "📎";
    font-size: 1.2rem;
}

.file-name {
    display: block;
    margin-top: 8px;
    font-size: 0.85rem;
    color: #718096;
    font-style: italic;
}

.reply-actions button {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
    width: 100%;
    font-size: 1rem;
}

.reply-actions button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.reply-heading {
    font-size: 1.5rem;
    color: #2d3748;
    margin: 40px 0 20px 0;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
}

.reply-box {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 3px solid #667eea;
}

.reply-box strong {
    color: #2d3748;
    font-size: 1rem;
    display: block;
    margin-bottom: 10px;
}

.reply-box p {
    color: #4a5568;
    line-height: 1.6;
    margin: 10px 0;
}

.reply-box .blog-actions {
    border-top: 1px solid #e2e8f0;
    border-bottom: none;
    margin-top: 15px;
    padding-top: 15px;
    padding-bottom: 0;
}

.time-warning {
    font-size: 0.75rem;
    color: #718096;
    font-style: italic;
    margin-top: 5px;
}

/* Responsive design */
@media (max-width: 768px) {
    .posts-container {
        padding: 20px;
    }
    
    .post-title {
        font-size: 1.8rem;
    }
    
    .blog-actions {
        flex-direction: column;
    }
    
    .actions-left,
    .actions-right {
        width: 100%;
    }
    
    .blog-actions a,
    .blog-actions .like-link {
        justify-content: center;
        width: 100%;
    }
}
</style>

<main class="page-blog">

    <div class="posts-container">
        <article class="post-card">

            <!-- BLOG TITLE -->
            <h1 class="post-title"><?= htmlspecialchars($blog['title']) ?></h1>

            <!-- SUCCESS MESSAGE -->
            <?php if (!empty($success)): ?>
                <div class="success-message">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- BLOG CATEGORY -->
            <?php if ($blog['topic_name']): ?>
                <div class="post-category">
    ✍️ Written by <?= htmlspecialchars($blog['author']) ?>
</div>

            <?php endif; ?>

            <!-- BLOG CONTENT -->
            <div class="post-content">
                <?= nl2br(htmlspecialchars($blog['content'])) ?>
            </div>

            <!-- BLOG MEDIA -->
            <?php if ($blog['media']): ?>
                <img src="<?= htmlspecialchars($blog['media']) ?>" class="post-media">
            <?php endif; ?>

            <!-- BLOG ACTIONS -->
            <div class="blog-actions">
                <div class="actions-left">
                    <?php if ($is_logged_in): ?>
                        <a class="like-link" data-id="<?= $blog_id ?>" data-type="blog">
                            <span class="like-heart">🤍</span> Like (<span><?= $blog_like_count ?></span>)
                        </a>
                        <a onclick="toggleReplyForm('blog')">💬 Reply</a>
                    <?php endif; ?>
                </div>
                <div class="actions-right">
                    <?php if ($is_logged_in && $can_edit_blog): ?>
                        <a href="edit_post.php?id=<?= $blog_id ?>" class="btn-edit">✏️ Edit</a>
                        <a href="delete_post.php?id=<?= $blog_id ?>" class="btn-delete" onclick="return confirm('Delete this blog?');">🗑 Delete</a>
                    <?php elseif ($is_logged_in && $blog['user_id'] == $user_id && $blog['hours_since_creation'] >= 24): ?>
                        <span class="btn-disabled" title="Edit period expired (24 hours)">✏️ Edit</span>
                        <span class="btn-disabled" title="Delete period expired (24 hours)">🗑 Delete</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_logged_in && $blog['user_id'] == $user_id && $blog['hours_since_creation'] >= 24 && $role !== 'admin'): ?>
                <div class="time-warning">
                    ⏰ Edit/Delete period expired. You can only modify posts within 24 hours of creation.
                </div>
            <?php endif; ?>

            <!-- BLOG REPLY FORM -->
            <?php if ($is_logged_in): ?>
                <div class="reply-form-wrap" id="reply-form-blog" style="display:none;">
                    <form method="post" action="save_reply.php" enctype="multipart/form-data" class="reply-form">
                        <input type="hidden" name="blog_id" value="<?= $blog_id ?>">
                        <input type="hidden" name="parent_reply_id" value="0">
                        <textarea name="reply" required placeholder="Write your reply..."></textarea>
                        <div class="reply-actions">
                            <div class="file-input-wrapper">
                                <input type="file" name="reply_file" id="file-blog" onchange="updateFileName(this, 'filename-blog')">
                                <label for="file-blog" class="file-input-label">
                                    Choose File (Optional)
                                </label>
                                <span class="file-name" id="filename-blog"></span>
                            </div>
                            <button type="submit">Post Reply</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- REPLIES -->
            <h3 class="reply-heading">Replies</h3>
            <?php foreach ($replies as $r): 
                /* CHECK IF USER CAN EDIT/DELETE REPLY (24 hour rule) */
                $can_edit_reply = false;
                if ($is_logged_in) {
                    if ($role === 'admin') {
                        $can_edit_reply = true;
                    } elseif ($r['user_id'] == $user_id && $r['hours_since_creation'] < 24) {
                        $can_edit_reply = true;
                    }
                }
            ?>
                <div class="reply-box">
                   <strong><?= htmlspecialchars($r['user_name']) ?></strong>

                    <p><?= nl2br(htmlspecialchars($r['reply_text'])) ?></p>

                    <div class="blog-actions">
                        <div class="actions-left">
                            <?php if ($is_logged_in): ?>
                                <a class="like-link" data-id="<?= $r['reply_id'] ?>" data-type="reply">
                                    <span class="like-heart">🤍</span> Like (<span><?= $r['like_count'] ?></span>)
                                </a>
                                <a onclick="toggleReplyForm(<?= $r['reply_id'] ?>)">💬 Reply</a>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_logged_in && $can_edit_reply): ?>
                            <div class="actions-right">
                                <a href="edit_reply.php?id=<?= $r['reply_id'] ?>" class="btn-edit">✏️ Edit</a>
                                <a href="delete_reply.php?id=<?= $r['reply_id'] ?>" class="btn-delete" onclick="return confirm('Delete this reply?');">🗑 Delete</a>
                            </div>
                        <?php elseif ($is_logged_in && $r['user_id'] == $user_id && $r['hours_since_creation'] >= 24): ?>
                            <div class="actions-right">
                                <span class="btn-disabled" title="Edit period expired (24 hours)">✏️ Edit</span>
                                <span class="btn-disabled" title="Delete period expired (24 hours)">🗑 Delete</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in && $r['user_id'] == $user_id && $r['hours_since_creation'] >= 24 && $role !== 'admin'): ?>
                        <div class="time-warning">
                            ⏰ Edit/Delete period expired. You can only modify replies within 24 hours of creation.
                        </div>
                    <?php endif; ?>

                    <!-- REPLY FORM FOR THIS REPLY -->
                    <?php if ($is_logged_in): ?>
                        <div class="reply-form-wrap" id="reply-form-<?= $r['reply_id'] ?>" style="display:none;">
                            <form method="post" action="save_reply.php" enctype="multipart/form-data" class="reply-form">
                                <input type="hidden" name="blog_id" value="<?= $blog_id ?>">
                                <input type="hidden" name="parent_reply_id" value="<?= $r['reply_id'] ?>">
                                <textarea name="reply" required placeholder="Write your reply..."></textarea>
                                <div class="reply-actions">
                                    <div class="file-input-wrapper">
                                        <input type="file" name="reply_file" id="file-reply-<?= $r['reply_id'] ?>" onchange="updateFileName(this, 'filename-reply-<?= $r['reply_id'] ?>')">
                                        <label for="file-reply-<?= $r['reply_id'] ?>" class="file-input-label">
                                            Choose File (Optional)
                                        </label>
                                        <span class="file-name" id="filename-reply-<?= $r['reply_id'] ?>"></span>
                                    </div>
                                    <button type="submit">Post Reply</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </article>
        <div class="blog-actions">
            <a href="category.php?id=<?= $blog_id ?>">← Back to Blog</a>
        </div>
    </div>
</main>

<script>
    let activeForm = null;

    function toggleReplyForm(id) {
        const form = document.getElementById('reply-form-' + id);
        if (!form) return;

        if (activeForm && activeForm !== form) {
            activeForm.style.display = 'none';
        }
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
        activeForm = form.style.display === 'block' ? form : null;
    }

    function updateFileName(input, spanId) {
        const span = document.getElementById(spanId);
        if (input.files && input.files.length > 0) {
            span.textContent = '📄 ' + input.files[0].name;
        } else {
            span.textContent = '';
        }
    }

    document.querySelectorAll('.like-link').forEach(link => {
        link.onclick = async () => {
            const heart = link.querySelector('.like-heart');
            const res = await fetch('like.php', {
                method: 'POST',
                body: new URLSearchParams({
                    target_id: link.dataset.id,
                    target_type: link.dataset.type
                })
            });
            const data = await res.json();
            if (data.status) {
                link.querySelector('span:nth-child(2)').textContent = data.count;
                // Heart fill toggle
                heart.textContent = heart.textContent === '🤍' ? '❤️' : '🤍';
            }
        };
    });

    // Auto-open blog reply if redirected
    const params = new URLSearchParams(window.location.search);
    if (params.get('reply') === '1') {
        const form = document.getElementById('reply-form-blog');
        if (form) {
            form.style.display = 'block';
            activeForm = form;
        }
    }
</script>

<?php
include 'footer.php';
$conn->close();
?>
