<?php
/**
 * Contrôleur Administrateur
 * Gestion du tableau de bord et des opérations administratives
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'models/UtilisateurModel.php';
require_once ROOT_PATH . 'models/CoursModel.php';

class AdminController extends Controller {
    
    private UtilisateurModel $utilisateurModel;
    private CoursModel $coursModel;
    
    public function __construct() {
        $this->utilisateurModel = new UtilisateurModel();
        $this->coursModel = new CoursModel();
    }
    
    /**
     * Tableau de bord administrateur
     */
    public function dashboard(): void {
        $this->requireAdmin();
        
        $statsUsers  = $this->utilisateurModel->getStatistiques();
        $statsCours  = $this->coursModel->getStatistiques();
        $topCours    = $this->coursModel->findAllComplets();
        $rapportCat  = $this->coursModel->rapportParCategorie();
        
        $this->render('admin/dashboard', [
            'title'      => 'Tableau de Bord - Admin',
            'statsUsers' => $statsUsers,
            'statsCours' => $statsCours,
            'topCours'   => array_slice($topCours, 0, 5),
            'rapportCat' => $rapportCat,
        ], 'admin');
    }
    
    /**
     * Liste de tous les utilisateurs
     */
    public function utilisateurs(): void {
        $this->requireAdmin();
        
        $role    = $this->input('role', '');
        $terme   = $this->input('q', '');
        $page    = max(1, (int) $this->input('page', 1));
        
        if ($terme) {
            $users = $this->utilisateurModel->rechercher($terme, $role);
        } elseif ($role) {
            $users = $this->utilisateurModel->findByRole($role);
        } else {
            $users = $this->utilisateurModel->findAll('nom');
        }
        
        $this->render('admin/utilisateurs', [
            'title'  => 'Gestion des Utilisateurs',
            'users'  => $users,
            'role'   => $role,
            'terme'  => $terme,
            'page'   => $page,
        ], 'admin');
    }
    
    /**
     * Ajouter un utilisateur
     */
    public function ajouterUtilisateur(): void {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom'          => $this->input('nom'),
                'prenom'       => $this->input('prenom'),
                'email'        => $this->input('email'),
                'mot_de_passe' => $_POST['mot_de_passe'],
                'role'         => $this->input('role', 'etudiant'),
                'actif'        => 1,
            ];
            
            if ($this->utilisateurModel->emailExiste($data['email'])) {
                $this->flash('error', 'Cet email est déjà utilisé.');
            } else {
                $id = $this->utilisateurModel->creerCompte($data);
                if ($id) {
                    $this->flash('success', 'Utilisateur créé avec succès (ID: ' . $id . ')');
                    $this->redirect('admin/utilisateurs');
                }
            }
        }
        
        $this->render('admin/ajouter_utilisateur', [
            'title' => 'Ajouter un Utilisateur',
        ], 'admin');
    }
    
    /**
     * Modifier un utilisateur
     */
    public function modifierUtilisateur(int $id): void {
        $this->requireAdmin();
        
        $user = $this->utilisateurModel->findById($id);
        if (!$user) {
            $this->flash('error', 'Utilisateur introuvable.');
            $this->redirect('admin/utilisateurs');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom'    => $this->input('nom'),
                'prenom' => $this->input('prenom'),
                'email'  => $this->input('email'),
                'role'   => $this->input('role'),
                'actif'  => $this->input('actif', 1),
            ];
            
            // Changer le mot de passe seulement si fourni
            if (!empty($_POST['nouveau_mdp'])) {
                $this->utilisateurModel->changerMotDePasse($id, $_POST['nouveau_mdp']);
            }
            
            if ($this->utilisateurModel->update($id, $data)) {
                $this->flash('success', 'Utilisateur mis à jour avec succès.');
                $this->redirect('admin/utilisateurs');
            }
        }
        
        $this->render('admin/modifier_utilisateur', [
            'title' => 'Modifier l\'Utilisateur',
            'user'  => $user,
        ], 'admin');
    }
    
    /**
     * Supprimer (désactiver) un utilisateur
     */
    public function supprimerUtilisateur(int $id): void {
        $this->requireAdmin();
        
        // Ne pas supprimer son propre compte
        if ($id === $_SESSION['user']['id']) {
            $this->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('admin/utilisateurs');
        }
        
        // Soft delete : désactiver le compte
        if ($this->utilisateurModel->update($id, ['actif' => 0])) {
            $this->flash('success', 'Compte désactivé avec succès.');
        } else {
            $this->flash('error', 'Erreur lors de la suppression.');
        }
        
        $this->redirect('admin/utilisateurs');
    }
    
    /**
     * Gestion des cours
     */
    public function cours(): void {
        $this->requireAdmin();
        
        $cours = $this->coursModel->findAllComplets();
        
        $this->render('admin/cours', [
            'title' => 'Gestion des Cours',
            'cours' => $cours,
        ], 'admin');
    }
    
    /**
     * Rapport et statistiques (génération de rapport)
     */
    public function rapports(): void {
        $this->requireAdmin();
        
        $statsUsers  = $this->utilisateurModel->getStatistiques();
        $statsCours  = $this->coursModel->getStatistiques();
        $rapportCat  = $this->coursModel->rapportParCategorie();
        
        $this->render('admin/rapports', [
            'title'      => 'Rapports et Statistiques',
            'statsUsers' => $statsUsers,
            'statsCours' => $statsCours,
            'rapportCat' => $rapportCat,
        ], 'admin');
    }
}
