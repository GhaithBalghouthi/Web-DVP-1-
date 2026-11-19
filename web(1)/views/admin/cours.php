<?php /* Vue : Gestion des Cours (Admin) */ ?>

<div class="container" style="padding:40px 20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px">
    <h1 style="color:var(--primary);font-size:1.5rem"><i class="fas fa-book"></i> Gestion des Cours</h1>
    <span style="color:var(--gray);font-size:0.92rem"><?= count($cours) ?> cours au total</span>
  </div>

  <?php if (isset($_SESSION['flash'])): ?>
    <?php foreach ($_SESSION['flash'] as $type => $msg): ?>
      <div class="alert alert-<?= $type ?>" style="margin-bottom:20px">
        <i class="fas fa-<?= $type==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= $msg ?>
        <button class="close-alert" onclick="this.parentElement.remove()">×</button>
      </div>
    <?php endforeach; unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <h3><i class="fas fa-list"></i> Liste des cours</h3>
    </div>
    <div style="overflow-x:auto">
      <?php if (empty($cours)): ?>
        <p style="padding:40px;text-align:center;color:var(--gray)">Aucun cours disponible.</p>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Titre</th>
              <th>Catégorie</th>
              <th>Niveau</th>
              <th>Professeur</th>
              <th>Durée</th>
              <th>Inscrits</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cours as $c): ?>
            <tr>
              <td><?= $c['id_cours'] ?></td>
              <td>
                <strong><?= htmlspecialchars($c['titre']) ?></strong>
                <br><small style="color:var(--gray)"><?= $c['nb_lecons'] ?> leçons</small>
              </td>
              <td>
                <span style="background:<?= htmlspecialchars($c['couleur'] ?? '#333') ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:0.78rem">
                  <?= htmlspecialchars($c['nom_categorie']) ?>
                </span>
              </td>
              <td><?= ucfirst($c['niveau']) ?></td>
              <td><?= htmlspecialchars($c['nom_professeur']) ?></td>
              <td><?= $c['duree_heures'] ?>h</td>
              <td><i class="fas fa-users"></i> <?= $c['nb_inscrits'] ?></td>
              <td>
                <span style="background:<?= $c['actif']?'#d4edda':'#f8d7da' ?>;color:<?= $c['actif']?'#155724':'#721c24' ?>;padding:3px 10px;border-radius:20px;font-size:0.8rem">
                  <?= $c['actif'] ? 'Actif' : 'Inactif' ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
