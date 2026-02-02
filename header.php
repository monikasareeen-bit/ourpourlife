<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php"; // 🔐 IMPORTANT: header must always have DB

$is_logged_in = isset($_SESSION['user_id']);

/* 🌸 Topic → Emoji Mapping */
$categoryEmojis = [
    'Boundaries & Self-Respect' => '🛡️',
    'Emotional Wellbeing'      => '💗',
    'Healing & Growth'         => '🌱',
    'Inner Strength'           => '💪',
    'Life Reflections'         => '🌙',
    'Mental Peace'             => '🧘‍♀️',
    'Personal Stories'         => '📖',
    'Relationships'            => '💞',
    'Self Love'                => '💖',
    'Womanhood'                => '🌸'
];

/* FETCH TOPICS SAFELY */
$topics = [];

$res = mysqli_query(
    $conn,
    "SELECT topic_id, topic_name FROM topics ORDER BY topic_name"
);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $topics[] = $row;
    }
}
?>

<link rel="stylesheet" href="style.css">

<header class="site-header">
    <nav class="navbar">

        <!-- LOGO -->
        <div class="logo-area">
            <img src="Outpourlife.png" class="logo" alt="Outpourlife">
            <span class="site-title">Outpourlife</span>
        </div>

        <!-- NAV LINKS -->
        <div class="nav-links">

            <a href="home.php">Home</a>
            <a href="about.php">About Us</a>

            <!-- CATEGORY DROPDOWN -->
            <div class="dropdown" id="categoryDropdown">
                <span class="dropdown-toggle">🌼 Explore Topics</span>

                <div class="dropdown-menu">
                    <?php if (!empty($topics)): ?>
                        <?php foreach ($topics as $t): 
                            $name  = $t['topic_name'];
                            $emoji = $categoryEmojis[$name] ?? '🌷';
                        ?>
                            <a href="category.php?topic_id=<?= (int)$t['topic_id'] ?>">
                                <?= $emoji . ' ' . htmlspecialchars($name) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="dropdown-empty">
                            No topics available
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_logged_in): ?>
                <a href="logout.php" class="nav-btn signup-btn">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="signup.php" class="nav-btn signup-btn">Signup</a>
            <?php endif; ?>

        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.dropdown-toggle');
    const dropdown = document.getElementById('categoryDropdown');

    if (toggle && dropdown) {
        toggle.addEventListener('click', () => {
            dropdown.classList.toggle('active');
        });

        // Close on outside click
        document.addEventListener('click', e => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }
});
</script>
