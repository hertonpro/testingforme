<?php if (Auth::check()): ?>
            </div>
        </main>

        <nav class="bottom-nav">
            <a href="index.php?page=dashboard" class="bottom-nav__link <?= ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="index.php?page=test_form" class="bottom-nav__link <?= ($_GET['page'] ?? '') === 'test_form' ? 'active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Nouveau</span>
            </a>
            <a href="index.php?page=test_list" class="bottom-nav__link <?= ($_GET['page'] ?? '') === 'test_list' ? 'active' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span>Tests</span>
            </a>
        </nav>
        <?php endif; ?>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
