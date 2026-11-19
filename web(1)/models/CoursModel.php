<?php
/**
 * Modèle Cours
 * Gestion des cours de la plateforme e-learning
 */

require_once ROOT_PATH . 'core/Model.php';

class CoursModel extends Model {
    
    protected string $table = 'cours';
    protected string $primaryKey = 'id_cours';
    
    /**
     * Récupérer tous les cours avec leurs informations complètes
     * (jointures avec categories et utilisateurs)
     * 
     * @return array Liste des cours enrichis
     */
    public function findAllComplets(): array {
        $sql = "SELECT 
                    c.id_cours, c.titre, c.description, c.niveau,
                    c.duree_heures, c.nb_lecons, c.image, c.date_creation, c.actif,
                    cat.nom_categorie, cat.couleur, cat.icone,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_professeur,
                    COUNT(DISTINCT i.id_inscription) AS nb_inscrits
                FROM cours c
                INNER JOIN categories cat ON c.id_categorie = cat.id_categorie
                INNER JOIN utilisateurs u ON c.id_professeur = u.id_user
                LEFT JOIN inscriptions i ON c.id_cours = i.id_cours
                WHERE c.actif = TRUE
                GROUP BY c.id_cours, c.titre, c.description, c.niveau,
                         c.duree_heures, c.nb_lecons, c.image, c.date_creation,
                         c.actif, cat.nom_categorie, cat.couleur, cat.icone,
                         u.prenom, u.nom
                ORDER BY c.date_creation DESC";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Récupérer un cours complet par son ID
     * 
     * @param int $id ID du cours
     * @return array|false Données du cours ou false
     */
    public function findCompletById(int $id): array|false {
        $sql = "SELECT 
                    c.*, 
                    cat.nom_categorie, cat.couleur, cat.icone,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_professeur,
                    u.email AS email_professeur,
                    COUNT(DISTINCT i.id_inscription) AS nb_inscrits,
                    AVG(i.progression) AS progression_moyenne
                FROM cours c
                INNER JOIN categories cat ON c.id_categorie = cat.id_categorie
                INNER JOIN utilisateurs u ON c.id_professeur = u.id_user
                LEFT JOIN inscriptions i ON c.id_cours = i.id_cours
                WHERE c.id_cours = :id
                GROUP BY c.id_cours, cat.nom_categorie, cat.couleur, cat.icone,
                         u.prenom, u.nom, u.email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Recherche avancée multicritères
     * 
     * @param string $terme Terme de recherche
     * @param int $categorie ID de catégorie (0 = toutes)
     * @param string $niveau Niveau du cours ('' = tous)
     * @return array Résultats
     */
    public function rechercher(string $terme = '', int $categorie = 0, string $niveau = ''): array {
        $sql = "SELECT c.id_cours, c.titre, c.description, c.niveau, c.duree_heures,
                       c.nb_lecons, c.image, cat.nom_categorie, cat.couleur,
                       CONCAT(u.prenom, ' ', u.nom) AS nom_professeur,
                       COUNT(DISTINCT i.id_inscription) AS nb_inscrits
                FROM cours c
                INNER JOIN categories cat ON c.id_categorie = cat.id_categorie
                INNER JOIN utilisateurs u ON c.id_professeur = u.id_user
                LEFT JOIN inscriptions i ON c.id_cours = i.id_cours
                WHERE c.actif = TRUE";
        $params = [];
        
        if ($terme) {
            $sql .= " AND (c.titre LIKE :terme OR c.description LIKE :terme)";
            $params[':terme'] = "%{$terme}%";
        }
        if ($categorie > 0) {
            $sql .= " AND c.id_categorie = :categorie";
            $params[':categorie'] = $categorie;
        }
        if ($niveau) {
            $sql .= " AND c.niveau = :niveau";
            $params[':niveau'] = $niveau;
        }
        
        $sql .= " GROUP BY c.id_cours, c.titre, c.description, c.niveau, c.duree_heures,
                           c.nb_lecons, c.image, cat.nom_categorie, cat.couleur, u.prenom, u.nom
                  ORDER BY c.titre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les cours auxquels un étudiant est inscrit
     * 
     * @param int $idEtudiant ID de l'étudiant
     * @return array Cours de l'étudiant
     */
    public function findByEtudiant(int $idEtudiant): array {
        $sql = "SELECT c.id_cours, c.titre, c.description, c.niveau, c.image,
                       cat.nom_categorie, cat.couleur,
                       i.statut, i.progression, i.date_inscription,
                       COUNT(q.id_quiz) AS nb_quiz
                FROM cours c
                INNER JOIN inscriptions i ON c.id_cours = i.id_cours
                INNER JOIN categories cat ON c.id_categorie = cat.id_categorie
                LEFT JOIN quiz q ON c.id_cours = q.id_cours AND q.actif = TRUE
                WHERE i.id_etudiant = :id_etudiant
                GROUP BY c.id_cours, c.titre, c.description, c.niveau, c.image,
                         cat.nom_categorie, cat.couleur, i.statut, i.progression, i.date_inscription
                ORDER BY i.date_inscription DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_etudiant' => $idEtudiant]);
        return $stmt->fetchAll();
    }
    
    /**
     * Statistiques des cours pour le tableau de bord
     * 
     * @return array Statistiques globales
     */
    public function getStatistiques(): array {
        $sql = "SELECT 
                    COUNT(*) AS total_cours,
                    SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) AS cours_actifs,
                    SUM(CASE WHEN niveau = 'debutant' THEN 1 ELSE 0 END) AS debutant,
                    SUM(CASE WHEN niveau = 'intermediaire' THEN 1 ELSE 0 END) AS intermediaire,
                    SUM(CASE WHEN niveau = 'avance' THEN 1 ELSE 0 END) AS avance,
                    AVG(duree_heures) AS duree_moyenne
                FROM cours";
        return $this->db->query($sql)->fetch();
    }
    
    /**
     * Récupérer les cours par catégorie avec le nombre d'inscrits
     * 
     * @return array Rapport par catégorie
     */
    public function rapportParCategorie(): array {
        $sql = "SELECT 
                    cat.nom_categorie,
                    cat.couleur,
                    cat.icone,
                    COUNT(DISTINCT c.id_cours) AS nb_cours,
                    COUNT(DISTINCT i.id_inscription) AS nb_inscriptions,
                    AVG(i.progression) AS progression_moyenne
                FROM categories cat
                LEFT JOIN cours c ON cat.id_categorie = c.id_categorie AND c.actif = TRUE
                LEFT JOIN inscriptions i ON c.id_cours = i.id_cours
                GROUP BY cat.id_categorie, cat.nom_categorie, cat.couleur, cat.icone
                ORDER BY nb_cours DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
