<?php
// ============================================================
// Page : Gestion des utilisateurs (Administrateur)
// ============================================================

Auth::requireRole('administrateur');
$pageTitle = 'Utilisateurs';

// Création d'un utilisateur
if (isPost() && isset($_POST['action'])) {
    $data = clean($_POST);

    if ($data['action'] === 'create') {
        $nom = $data['nom'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['mot_de_passe'] ?? '';
        $role = $data['role'] ?? 'technicien';
        $equipe = $data['equipe'] ?? '';
        $entreprise = $data['entreprise'] ?? '';

        if (empty($nom) || empty($email) || empty($password)) {
            flash('error', 'Veuillez remplir tous les champs obligatoires.');
        } else {
            $stmt = Database::prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                flash('error', 'Cet email est déjà utilisé.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = Database::prepare('INSERT INTO users (nom, email, mot_de_passe, role, equipe, entreprise) VALUES (:nom, :email, :mdp, :role, :equipe, :entreprise)');
                $insert->execute([
                    ':nom' => $nom,
                    ':email' => $email,
                    ':mdp' => $hash,
                    ':role' => $role,
                    ':equipe' => $equipe,
                    ':entreprise' => $entreprise,
                ]);
                flash('success', 'Utilisateur créé avec succès.');
            }
        }
        redirect('index.php?page=users');
    }

    if ($data['action'] === 'delete' && isset($data['user_id'])) {
        $userId = (int)$data['user_id'];
        if ($userId === Auth::userId()) {
            flash('error', 'Vous ne pouvez pas vous supprimer vous-même.');
        } else {
            $del = Database::prepare('DELETE FROM users WHERE id = :id');
            $del->execute([':id' => $userId]);
            flash('success', 'Utilisateur supprimé.');
        }
        redirect('index.php?page=users');
    }
}

// Récupérer tous les utilisateurs
$users = Database::query('SELECT id, nom, email, role, equipe, entreprise, created_at FROM users ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header--between">
    <div>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-desc"><?= count($users) ?> utilisateur(s)</p>
    </div>
    <button class="btn btn--primary" onclick="
        var form = document.getElementById('userForm');
        var open = form.style.display !== 'none';
        form.style.display = open ? 'none' : 'block';
        this.textContent = open ? 'Ajouter' : 'Fermer';
    ">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Ajouter</span>
    </button>
</div>

<!-- Formulaire création -->
<div class="card" id="userForm" style="display:none">
    <div class="card__header">
        <h2 class="card__title">Nouvel utilisateur</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="index.php?page=users">
            <input type="hidden" name="action" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom complet *</label>
                    <input type="text" name="nom" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="mot_de_passe" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Rôle</label>
                    <select name="role" class="form-input">
                        <option value="technicien">Technicien</option>
                        <option value="superviseur">Superviseur</option>
                        <option value="administrateur">Administrateur</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Équipe</label>
                    <input type="text" name="equipe" class="form-input" placeholder="Ex: Équipe terrain">
                </div>
                <div class="form-group">
                    <label class="form-label">Entreprise</label>
                    <input type="text" name="entreprise" class="form-input" placeholder="Ex: HGR Panzi">
                </div>
            </div>
            <button type="submit" class="btn btn--primary">Créer l'utilisateur</button>
        </form>
    </div>
</div>

<!-- Liste -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Liste des utilisateurs</h2>
    </div>
    <div class="card__body">
        <div class="user-list">
            <?php foreach ($users as $u): ?>
            <div class="user-item">
                <div class="user-item__info">
                    <span class="user-item__name"><?= h($u['nom']) ?></span>
                    <span class="user-item__email"><?= h($u['email']) ?></span>
                    <span class="user-item__meta">
                        <?= h($u['equipe'] ?? '') ?> <?= $u['equipe'] && $u['entreprise'] ? '—' : '' ?> <?= h($u['entreprise'] ?? '') ?>
                    </span>
                </div>
                <div class="user-item__right">
                    <span class="badge badge--<?= $u['role'] === 'administrateur' ? 'info' : 'success' ?>">
                        <?= $u['role'] ?>
                    </span>
                    <?php if ((int)$u['id'] !== Auth::userId()): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer <?= h($u['nom']) ?> ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn--danger btn--sm">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.user-list { display: flex; flex-direction: column; }
.user-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--color-border-light); gap: 12px; }
.user-item:last-child { border-bottom: none; }
.user-item__info { flex: 1; min-width: 0; }
.user-item__name { display: block; font-weight: 600; font-size: 0.95rem; }
.user-item__email { display: block; font-size: 0.8rem; color: var(--color-text-secondary); }
.user-item__meta { display: block; font-size: 0.75rem; color: var(--color-text-light); }
.user-item__right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.btn--sm { padding: 4px 10px; font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
