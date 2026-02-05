<?php
session_start();
include "db.php";
include "header.php";
?>

<main class="about-page">

    <!-- Hero Section -->
    <section class="about-hero">
        <h1>About Outpourlife</h1>
        <p>A community where voices matter, stories inspire, and connections flourish</p>
    </section>

    <!-- Main Content -->
    <div class="about-container">

        <section class="about-section">
            <div class="about-content">
                <h2>Our Story</h2>
                <p>
                    Outpourlife was born from a simple belief: Everyone has a story worth sharing.
                </p>
                <p>
                    What started as a small blogging platform has grown into a vibrant community.
                </p>

                <h2>Our Mission</h2>
                <p>
                    We believe storytelling builds connection, empathy, and growth.
                </p>

                <h2>Why Choose Outpourlife?</h2>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">✍️</div>
                        <h3>Easy to Use</h3>
                        <p>Start writing in seconds.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🌟</div>
                        <h3>Engaged Community</h3>
                        <p>Readers who value authentic stories.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🎨</div>
                        <h3>Creative Freedom</h3>
                        <p>Express yourself freely.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="cta-section">
            <h2>Join Our Community Today</h2>
            <p>Share your story and connect with readers.</p>

            <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
                <a href="signup.php" class="cta-button">Start Writing Now</a>
            <?php else: ?>
                <a href="write_blog.php" class="cta-button">Write Your First Blog</a>
            <?php endif; ?>
        </div>

    </div>

</main>


<?php include "footer.php"; ?>
