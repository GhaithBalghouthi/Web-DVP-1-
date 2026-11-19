<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="http://localhost/projet-fixed/php-app/public/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Document</title>
</head>
<body>
    

<?php
/**
 * ESEN E-LEARN - Point d'entrée principal (Front Controller)
 * Tous les requêtes passent par ce fichier (routeur MVC)
 * 
 * URL structure: index.php?ctrl=NomController&action=methode&id=X
 * Ou avec mod_rewrite: /ctrl/action/id
 */

// Démarrer la session
session_name('esen_session');
session_start();

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Définir le chemin racine
define('ROOT_PATH', __DIR__ . '/');

// Charger la configuration et les classes de base
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'core/Controller.php';

// ============================================================
// ROUTEUR SIMPLE
// ============================================================

// Récupérer le contrôleur et l'action depuis l'URL
$controllerName = $_GET['ctrl'] ?? 'public';
$action         = $_GET['action'] ?? 'index';
$id             = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Mapping des contrôleurs
$controllers = [
    'auth'        => 'AuthController',
    'admin'       => 'AdminController',
    'cours'       => 'CoursController',
    'etudiant'    => 'EtudiantController',
    'public'      => 'PublicController',
];

// Sécurisation : valider le nom du contrôleur
$controllerName = preg_replace('/[^a-z]/', '', strtolower($controllerName));

if (!isset($controllers[$controllerName])) {
    $controllerName = 'public';
    $action = 'notFound';
}

$controllerClass = $controllers[$controllerName];
$controllerFile  = ROOT_PATH . 'controllers/' . $controllerClass . '.php';

// Charger et instancier le contrôleur
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        
        // Sécurisation de l'action
        $action = preg_replace('/[^a-zA-Z]/', '', $action);
        
        if (method_exists($controller, $action)) {
            // Appeler la méthode avec ou sans ID
            if ($id !== null) {
                $controller->$action($id);
            } else {
                $controller->$action();
            }
        } else {
            // Action non trouvée
            http_response_code(404);
            require VIEWS_PATH . 'shared/404.php';
        }
    }
} else {
    http_response_code(404);
    require VIEWS_PATH . 'shared/404.php';
}
?>
</body>
</html>