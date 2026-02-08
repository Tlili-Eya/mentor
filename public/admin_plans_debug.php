<?php
// public/admin_plans_debug.php - Script de diagnostic performance

// Désactiver le temps d'exécution maximum
set_time_limit(0);

// Charger l'autoloader de Symfony
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
(new Dotenv())->bootEnv(__DIR__.'/../.env');

// Créer le kernel Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Récupérer la connexion à la base de données
$container = $kernel->getContainer();
$conn = $container->get('doctrine.dbal.default_connection');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Diagnostic Performance - Plans Admin</title>";
echo "<style>";
echo "body { font-family: 'Courier New', monospace; background: #f5f5f5; padding: 20px; }";
echo "pre { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".warning { color: #d35400; font-weight: bold; }";
echo ".success { color: #27ae60; }";
echo ".error { color: #c0392b; font-weight: bold; }";
echo "h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }";
echo ".section { margin-bottom: 30px; background: white; padding: 20px; border-radius: 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<h1>🔍 Diagnostic Performance - Plans Admin</h1>";
echo "<p><small>Généré le: " . date('d/m/Y H:i:s') . "</small></p>";

echo "<div class='section'>";
echo "<h2>1. Analyse table plan_actions</h2>";
try {
    $result = $conn->executeQuery("SELECT COUNT(*) as count FROM plan_actions")->fetchAssociative();
    $count = $result['count'] ?? 0;
    echo "<p>Nombre total de plans: <strong>" . $count . "</strong></p>";
    
    if ($count == 0) {
        echo "<p class='warning'>⚠️ TABLE VIDE - Aucun plan dans la base</p>";
    } elseif ($count > 1000) {
        echo "<p class='warning'>⚠️ TRÈS GRANDE TABLE (" . $count . " lignes)</p>";
    } elseif ($count > 100) {
        echo "<p class='warning'>⚠️ TABLE DE TAILLE MOYENNE (" . $count . " lignes)</p>";
    } else {
        echo "<p class='success'>✓ Table de petite taille (" . $count . " lignes)</p>";
    }
    
    // Voir quelques plans
    echo "<p><strong>Derniers plans (max 5):</strong></p>";
    $recentPlans = $conn->executeQuery("
        SELECT id, decision, statut, created_at 
        FROM plan_actions 
        ORDER BY id DESC 
        LIMIT 5
    ")->fetchAllAssociative();
    
    if ($recentPlans) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Décision</th><th>Statut</th><th>Créé le</th></tr>";
        foreach ($recentPlans as $plan) {
            echo "<tr>";
            echo "<td>" . $plan['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($plan['decision'], 0, 50)) . "...</td>";
            echo "<td>" . $plan['statut'] . "</td>";
            echo "<td>" . $plan['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Indexes sur la table plan_actions</h2>";
try {
    $indexes = $conn->executeQuery("SHOW INDEX FROM plan_actions")->fetchAllAssociative();
    
    if (count($indexes) <= 1) {
        echo "<p class='error'>❌ AUCUN INDEX (sauf PRIMARY KEY)</p>";
        echo "<p><strong>Indexes recommandés:</strong></p>";
        echo "<pre>";
        echo "CREATE INDEX idx_plan_statut ON plan_actions(statut);\n";
        echo "CREATE INDEX idx_plan_updated ON plan_actions(updated_at);\n";
        echo "CREATE INDEX idx_plan_sortie ON plan_actions(sortie_ai_id);\n";
        echo "CREATE INDEX idx_plan_created ON plan_actions(created_at);\n";
        echo "</pre>";
    } else {
        echo "<p><strong>Indexes existants:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom</th><th>Colonne</th><th>Type</th><th>Unique</th></tr>";
        foreach ($indexes as $index) {
            echo "<tr>";
            echo "<td>" . $index['Key_name'] . "</td>";
            echo "<td>" . $index['Column_name'] . "</td>";
            echo "<td>" . $index['Index_type'] . "</td>";
            echo "<td>" . ($index['Non_unique'] ? 'Non' : 'Oui') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Test de performance</h2>";
try {
    // Test 1: Requête simple
    echo "<p><strong>Test 1 - Requête simple (10 premiers plans):</strong></p>";
    $start = microtime(true);
    $test = $conn->executeQuery("SELECT id, decision FROM plan_actions LIMIT 10")->fetchAllAssociative();
    $time1 = microtime(true) - $start;
    echo "<p>Temps: <strong>" . sprintf("%.4f", $time1) . " secondes</strong></p>";
    
    // Test 2: Requête avec jointure
    echo "<p><strong>Test 2 - Requête avec jointure (comme dans l'admin):</strong></p>";
    $start = microtime(true);
    $test2 = $conn->executeQuery("
        SELECT p.id, p.decision, s.categorie_sortie 
        FROM plan_actions p 
        LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id 
        ORDER BY p.updated_at DESC 
        LIMIT 10
    ")->fetchAllAssociative();
    $time2 = microtime(true) - $start;
    echo "<p>Temps: <strong>" . sprintf("%.4f", $time2) . " secondes</strong></p>";
    
    // Analyse
    if ($time1 > 0.5 || $time2 > 1.0) {
        echo "<p class='error'>⚠️ PERFORMANCE LENTE DÉTECTÉE</p>";
        if ($time2 > $time1 * 2) {
            echo "<p>La jointure ralentit la requête (" . sprintf("%.2f", $time2/$time1) . "x plus lente)</p>";
        }
    } else {
        echo "<p class='success'>✓ Performance acceptable</p>";
    }
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Analyse table sortie_ai</h2>";
try {
    $result = $conn->executeQuery("
        SELECT 
            COUNT(*) as count, 
            AVG(LENGTH(contenu)) as avg_size,
            MAX(LENGTH(contenu)) as max_size,
            MIN(LENGTH(contenu)) as min_size
        FROM sortie_ai
    ")->fetchAssociative();
    
    echo "<p>Nombre d'entrées: <strong>" . ($result['count'] ?? 0) . "</strong></p>";
    echo "<p>Taille moyenne du contenu: <strong>" . round($result['avg_size'] ?? 0) . " caractères</strong></p>";
    echo "<p>Taille max: <strong>" . ($result['max_size'] ?? 0) . " caractères</strong></p>";
    echo "<p>Taille min: <strong>" . ($result['min_size'] ?? 0) . " caractères</strong></p>";
    
    if (($result['avg_size'] ?? 0) > 10000) {
        echo "<p class='warning'>⚠️ CONTENU TRÈS LONG en moyenne</p>";
        echo "<p>Conseil: Ne chargez pas le contenu dans les listes, seulement dans les pages détail</p>";
    }
    
    // Vérifier les relations
    echo "<p><strong>Plans sans sortie_ai:</strong></p>";
    $noRelation = $conn->executeQuery("
        SELECT COUNT(*) as count 
        FROM plan_actions 
        WHERE sortie_ai_id IS NULL
    ")->fetchOne();
    echo "<p>" . $noRelation . " plans n'ont pas de sortie_ai associée</p>";
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>5. EXPLAIN d'une requête typique</h2>";
try {
    $explain = $conn->executeQuery("
        EXPLAIN SELECT p.*, s.contenu 
        FROM plan_actions p 
        LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id 
        ORDER BY p.updated_at DESC 
        LIMIT 10
    ")->fetchAllAssociative();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Table</th><th>Type</th><th>Possible Keys</th><th>Key</th><th>Rows</th><th>Extra</th></tr>";
    foreach ($explain as $row) {
        echo "<tr>";
        echo "<td>" . $row['table'] . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['possible_keys'] . "</td>";
        echo "<td>" . $row['key'] . "</td>";
        echo "<td>" . $row['rows'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Analyse
    foreach ($explain as $row) {
        if ($row['type'] == 'ALL') {
            echo "<p class='warning'>⚠️ Scan complet de table détecté pour '" . $row['table'] . "'</p>";
        }
        if (strpos($row['Extra'] ?? '', 'Using filesort') !== false) {
            echo "<p class='warning'>⚠️ Tri sur disque (filesort) détecté</p>";
        }
    }
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Recommandations</h2>";

echo "<h3>Si la table est vide ou petite :</h3>";
echo "<ul>";
echo "<li>Ajoutez des données de test</li>";
echo "<li>Utilisez les commandes Fixtures de Doctrine</li>";
echo "</ul>";

echo "<h3>Si la table est grande (> 1000 lignes) :</h3>";
echo "<ul>";
echo "<li>Ajoutez des indexes (voir section 2)</li>";
echo "<li>Utilisez la pagination (déjà implémentée)</li>";
echo "<li>Évitez de charger le contenu de sortie_ai dans les listes</li>";
echo "</ul>";

echo "<h3>Si sortie_ai.contenu est très long :</h3>";
echo "<ul>";
echo "<li>Chargez seulement dans la page détail</li>";
echo "<li>Utilisez LIMIT dans les requêtes</li>";
echo "<li>Évitez SELECT *</li>";
echo "</ul>";

echo "<h3>Commandes SQL pour optimiser :</h3>";
echo "<pre>";
echo "# Ajouter les indexes critiques\n";
echo "CREATE INDEX idx_plan_statut ON plan_actions(statut);\n";
echo "CREATE INDEX idx_plan_updated ON plan_actions(updated_at);\n";
echo "CREATE INDEX idx_plan_sortie ON plan_actions(sortie_ai_id);\n";
echo "CREATE INDEX idx_plan_created ON plan_actions(created_at);\n";
echo "\n";
echo "# Analyser les tables\n";
echo "ANALYZE TABLE plan_actions;\n";
echo "ANALYZE TABLE sortie_ai;\n";
echo "\n";
echo "# Vérifier la taille\n";
echo "SELECT \n";
echo "    TABLE_NAME,\n";
echo "    TABLE_ROWS,\n";
echo "    ROUND(DATA_LENGTH/1024/1024, 2) as 'Size (MB)'\n";
echo "FROM information_schema.TABLES \n";
echo "WHERE TABLE_SCHEMA = DATABASE();\n";
echo "</pre>";

echo "</div>";

echo "<div class='section'>";
echo "<h2>7. Actions immédiates</h2>";
echo "<ol>";
echo "<li><strong>Vérifiez si la table plan_actions a des données</strong></li>";
echo "<li><strong>Ajoutez les indexes si manquants</strong> (section 2)</li>";
echo "<li><strong>Testez /admin/plans après les corrections</strong></li>";
echo "<li><strong>Supprimez ce fichier après usage</strong> (rm public/admin_plans_debug.php)</li>";
echo "</ol>";
echo "</div>";

echo "</body>";
echo "</html>";