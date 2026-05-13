<?php
// ============================================================
// Page : Connexion
// ============================================================

$pageTitle = 'Connexion';
$error = '';

if (isPost()) {
    $data = clean($_POST);
    $email = $data['email'] ?? '';
    $password = $data['mot_de_passe'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (Auth::login($email, $password)) {
        flash('success', 'Connexion réussie. Bienvenue !');
        redirect('index.php?page=dashboard');
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth">
    <div class="auth__card">
        <div class="auth__header">
            <div class="auth__logo">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.5"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            </div>
            <h1 class="auth__title"><?= h(APP_NAME) ?></h1>
            <p class="auth__subtitle"><?= h(APP_ORG) ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert--error">
            <span class="alert__icon">✗</span>
            <span class="alert__message"><?= h($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login" class="auth__form" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-input" placeholder="exemple@email.com" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="mot_de_passe">Mot de passe</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn--primary btn--block">
                Se connecter
            </button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
