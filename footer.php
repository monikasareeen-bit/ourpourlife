<footer class="footer">
    <h2>Outpourlife</h2>
    <p>
        Share your thoughts, connect with readers,<br>
        and explore stories that matter 💜
    </p>

    <div style="margin-top:16px;">
        <a href="home.php">Home</a> ·
        <a href="about.php">About</a> ·
        <a href="category.php">Categories</a>
    </div>

    <p style="margin-top:20px;font-size:13px;opacity:.7">
        © <?= date('Y') ?> Outpourlife. All rights reserved.
    </p>
    <script>
        document.addEventListener("click", function (e) {
            const link = e.target.closest(".like-link");
            if (!link) return;

            const blogId = link.dataset.blogId;
            const countSpan = link.querySelector(".like-count");

            fetch("toggle_like.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "blog_id=" + blogId
            })
                .then(res => res.json())
                .then(data => {
                    if (data.error) return;

                    let count = parseInt(countSpan.innerText, 10);
                    count = data.liked ? count + 1 : count - 1;

                    link.innerHTML =
                        (data.liked ? "❤️ Like (" : "🤍 Like (") +
                        `<span class="like-count">${count}</span>)`;
                });
        });
    </script>

</footer>