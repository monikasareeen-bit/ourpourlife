<?php
session_start();
include "db.php";
include "header.php";

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

/* ERROR HANDLING */
$error = '';

$topic_id = (int)($_GET['topic_id'] ?? 0);

/* FETCH TOPICS */
$topics = [];
$res = mysqli_query($conn, "SELECT topic_id, topic_name, emoji FROM topics");

if (!$res) {
    $error = "Failed to load topics.";
} else {
    while ($t = mysqli_fetch_assoc($res)) {
        $topics[$t['topic_id']] = $t;
    }
}

/* PAGE TITLE */
$title = ($topic_id && isset($topics[$topic_id]))
    ? ($topics[$topic_id]['emoji'] ?? '') . ' ' . $topics[$topic_id]['topic_name']
    : 'All Blogs';

/* FETCH BLOGS WITH LIKE COUNT AND TIME CHECK */
$blogs = null;

if (empty($error)) {
    if ($topic_id) {
        $stmt = $conn->prepare("
            SELECT b.*,
            TIMESTAMPDIFF(HOUR, b.created_at, NOW()) AS hours_since_creation,
            (SELECT COUNT(*) FROM likes 
             WHERE target_id=b.blog_id AND target_type='blog') AS like_count
            FROM blogs b
            WHERE b.topic_id=?
            ORDER BY b.created_at DESC
        ");
        $stmt->bind_param("i", $topic_id);
    } else {
        $stmt = $conn->prepare("
            SELECT b.*,
            TIMESTAMPDIFF(HOUR, b.created_at, NOW()) AS hours_since_creation,
            (SELECT COUNT(*) FROM likes 
             WHERE target_id=b.blog_id AND target_type='blog') AS like_count
            FROM blogs b
            ORDER BY b.created_at DESC
        ");
    }

    if ($stmt->execute()) {
        $blogs = $stmt->get_result();
    } else {
        $error = "Failed to load blogs.";
    }
}
?>

<style>
.page-category {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.category-hero {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
}

.category-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
    color: #333;
}

.category-hero p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 20px;
}

.btn-primary {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 500;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.blog-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.blog-card h2 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #2d3748;
    line-height: 1.4;
}

.blog-card > p {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.blog-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}

.blog-actions a {
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
.like-btn {
    background-color: #fff5f5;
    color: #e53e3e;
    border: 1px solid #feb2b2;
}

.like-btn:hover {
    background-color: #fed7d7;
    border-color: #fc8181;
}

/* Reply button */
.blog-actions a[href*="reply"] {
    background-color: #ebf8ff;
    color: #3182ce;
    border: 1px solid #90cdf4;
}

.blog-actions a[href*="reply"]:hover {
    background-color: #bee3f8;
    border-color: #63b3ed;
}

/* Read More button */
.blog-actions a[href*="blog.php"]:not([href*="reply"]) {
    background-color: #f0fff4;
    color: #38a169;
    border: 1px solid #9ae6b4;
}

.blog-actions a[href*="blog.php"]:not([href*="reply"]):hover {
    background-color: #c6f6d5;
    border-color: #68d391;
}

/* Edit button */
.blog-actions a[href*="edit_post"] {
    background-color: #fffaf0;
    color: #dd6b20;
    border: 1px solid #fbd38d;
}

.blog-actions a[href*="edit_post"]:hover {
    background-color: #feebc8;
    border-color: #f6ad55;
}

/* Delete button */
.blog-actions a[href*="delete_post"] {
    background-color: #fff5f5;
    color: #c53030;
    border: 1px solid #fc8181;
}

.blog-actions a[href*="delete_post"]:hover {
    background-color: #fed7d7;
    border-color: #f56565;
}

/* Disabled button */
.btn-disabled {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    background-color: #f7fafc;
    color: #a0aec0;
    border: 1px solid #e2e8f0;
    cursor: not-allowed;
    opacity: 0.6;
}

.error-message {
    background-color: #fff5f5;
    color: #c53030;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #e53e3e;
    margin: 20px 0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #a0aec0;
    font-size: 1.1rem;
    grid-column: 1 / -1;
}

/* Responsive design */
@media (max-width: 768px) {
    .blog-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .category-hero h1 {
        font-size: 2rem;
    }
    
    .blog-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .blog-actions a {
        justify-content: center;
    }
}
</style>

<main class="page-category">

    <section class="category-hero">
        <h1><?= htmlspecialchars($title) ?></h1>
        <p>Thoughts, stories & conversations</p>

        <?php if ($is_logged_in && $topic_id): ?>
            <a href="write_blog.php?topic_id=<?= $topic_id ?>" class="btn-primary">
                ✍️ Write Blog
            </a>
        <?php endif; ?>
    </section>

    <section class="blog-grid">

        <?php if (!empty($error)): ?>

            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>

        <?php elseif ($blogs && $blogs->num_rows > 0): ?>

            <?php while ($b = $blogs->fetch_assoc()): 
                /* CHECK IF USER CAN EDIT/DELETE (24 hour rule) */
                $can_edit = false;
                if ($is_logged_in) {
                    if ($role === 'admin') {
                        $can_edit = true;
                    } elseif ($b['user_id'] == $user_id && $b['hours_since_creation'] < 24) {
                        $can_edit = true;
                    }
                }
            ?>

                <article class="blog-card">
                    <h2><?= htmlspecialchars($b['title']) ?></h2>

                    <p>
                        <?= htmlspecialchars(substr(strip_tags($b['content']), 0, 150)) ?>…
                    </p>

                    <div class="blog-actions">

                        <?php if ($is_logged_in): ?>
                            <a href="#" class="like-btn"
                               data-id="<?= $b['blog_id'] ?>"
                               data-type="blog">
                                🤍 Like (<span><?= (int)$b['like_count'] ?></span>)
                            </a>

                            <a href="blog.php?id=<?= $b['blog_id'] ?>&reply=1">
                                💬 Reply
                            </a>
                        <?php endif; ?>

                        <a href="blog.php?id=<?= $b['blog_id'] ?>">
                            📖 Read More
                        </a>

                        <?php if ($can_edit): ?>
                            <a href="edit_post.php?id=<?= $b['blog_id'] ?>">✏️ Edit</a>
                            <a href="delete_post.php?id=<?= $b['blog_id'] ?>"
                               onclick="return confirm('Delete blog?')">
                                🗑 Delete
                            </a>
                        <?php elseif ($is_logged_in && $b['user_id'] == $user_id && $b['hours_since_creation'] >= 24): ?>
                            <span class="btn-disabled" title="Edit period expired (24 hours)">✏️ Edit</span>
                            <span class="btn-disabled" title="Delete period expired (24 hours)">🗑 Delete</span>
                        <?php endif; ?>

                    </div>
                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-state">No blogs found</div>

        <?php endif; ?>

    </section>
</main>

<?php include "footer.php"; ?>

<!-- LIKE TOGGLE SCRIPT -->
<script>
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();

        fetch("toggle_like.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body:
                "target_id=" + btn.dataset.id +
                "&target_type=" + btn.dataset.type
        })
        .then(res => res.json())
        .then(data => {
            if (data.count !== undefined) {
                btn.querySelector("span").innerText = data.count;
            }
        });
    });
});
</script>
