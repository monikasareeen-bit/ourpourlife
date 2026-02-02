<?php
session_start();
include "header.php"; // header already loads db.php

$is_logged_in = isset($_SESSION['user_id']);
?>

<main class="home-page">

    <!-- HERO -->
    <section class="home-hero">
        <div class="hero-content">
            <h1>Where Women Pour Their Hearts 💜</h1>
            <p>
                Stories of healing, growth, love, boundaries and self-respect.
            </p>

            <?php if ($is_logged_in): ?>
                <a href="write_blog.php" class="btn-primary">
                    ✍️ Write Your Story
                </a>
            <?php else: ?>
                <a href="signup.php" class="btn-primary">
                    🌸 Join the Community
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- LATEST BLOGS -->
    <section class="latest-section">
        <h2>Latest Stories</h2>

        <div class="blog-grid">

            <?php
            $blogs = [];
            $res = $conn->query("
                SELECT b.blog_id, b.title, b.content, b.created_at, t.topic_name
                FROM blogs b
                LEFT JOIN topics t ON b.topic_id = t.topic_id
                ORDER BY b.created_at DESC
                LIMIT 6
            ");

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $blogs[] = $row;
                }
            }
            ?>

            <?php if (!empty($blogs)): ?>
                <?php foreach ($blogs as $b): ?>
                    <article class="blog-card">

                        <h3><?= htmlspecialchars($b['title']) ?></h3>

                        <span class="blog-meta">
                            <?= htmlspecialchars($b['topic_name'] ?? 'General') ?>
                            • <?= date('M d, Y', strtotime($b['created_at'])) ?>
                        </span>

                        <p>
                            <?= htmlspecialchars(substr(strip_tags($b['content']), 0, 120)) ?>…
                        </p>

                        <a href="blog.php?id=<?= (int)$b['blog_id'] ?>" class="read-link">
                            Read More →
                        </a>

                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    🌷 No stories yet. Be the first to share yours.
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php include "footer.php"; ?>
