<?php

namespace App\DataFixtures;

use App\Entity\CategorieArticle;
use App\Entity\Conversation;
use App\Entity\Feedback;
use App\Entity\Message;
use App\Entity\Objectif;
use App\Entity\PlanActions;
use App\Entity\Programme;
use App\Entity\ReferenceArticle;
use App\Entity\SortieAI;
use App\Entity\Utilisateur;
use App\Entity\Humeur;
use App\Entity\ProfilApprentissage;
use App\Enum\CategorieSortie;
use App\Enum\Cible;
use App\Enum\Criticite;
use App\Enum\Statut;
use App\Enum\Statutobj;
use App\Enum\TypeSortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Utilisateurs (ESPRIT context)
        $admin = new Utilisateur();
        $admin->setEmail('admin@esprit.tn');
        $admin->setRole('ADMINM');
        $admin->setMdp($this->hasher->hashPassword($admin, 'admin123'));
        $admin->setNom('Kammoun');
        $admin->setPrenom('Mohamed');
        $admin->setDateInscription(new \DateTime('-1 year'));
        $manager->persist($admin);

        $prof1 = new Utilisateur();
        $prof1->setEmail('prof1@esprit.tn');
        $prof1->setRole('ENSEIGNANT');
        $prof1->setMdp($this->hasher->hashPassword($prof1, 'prof123'));
        $prof1->setNom('Trabelsi');
        $prof1->setPrenom('Ali');
        $prof1->setDateInscription(new \DateTime('-6 months'));
        $manager->persist($prof1);

        $prof2 = new Utilisateur();
        $prof2->setEmail('prof2@esprit.tn');
        $prof2->setRole('ENSEIGNANT');
        $prof2->setMdp($this->hasher->hashPassword($prof2, 'prof123'));
        $prof2->setNom('Gharbi');
        $prof2->setPrenom('Sami');
        $prof2->setDateInscription(new \DateTime('-3 months'));
        $manager->persist($prof2);

        // 1.1 Étudiants (Pour les modules Psychologie et Portfolio)
        $students = [];
        $prenomsTunisiens = [
            'Ahmed', 'Sarra', 'Yassine', 'Inès', 'Marwen', 'Faten', 'Nour', 'Hamza', 'Lobna', 'Tarek',
            'Cyrine', 'Meriem', 'Omar', 'Aziz', 'Farah', 'Rania', 'Amine', 'Skander', 'Youssef', 'Malek'
        ];

        for ($i = 0; $i < 20; $i++) {
            $student = new Utilisateur();
            $prenom = $prenomsTunisiens[$i % count($prenomsTunisiens)];
            $student->setEmail(strtolower($prenom) . $i . '@esprit.tn');
            $student->setRole('ETUDIANT');
            $student->setMdp($this->hasher->hashPassword($student, 'student123'));
            $student->setNom('Ben ' . $prenom);
            $student->setPrenom($prenom);
            $student->setDateInscription(new \DateTime('-'.rand(30, 300).' days'));
            $manager->persist($student);
            $students[] = $student;
        }

        // 2. Catégories d'articles (ESPRIT context)
        $catIng = new CategorieArticle();
        $catIng->setNomCategorie('Ingénierie Logicielle');
        $catIng->setDescription('Ressources sur les modules Java, UML et Génie Logiciel.');
        $catIng->setAuteur($admin);
        $catIng->setCreatedAt(new \DateTime('-1 year'));
        $manager->persist($catIng);

        $catTech = new CategorieArticle();
        $catTech->setNomCategorie('Technologies Modernes');
        $catTech->setDescription('Ressources sur Unix, Réseaux et Machine Learning.');
        $catTech->setAuteur($admin);
        $catTech->setCreatedAt(new \DateTime('-1 year'));
        $manager->persist($catTech);

        // 3. Articles de Référence (ESPRIT context)
        $subjects = [
            'Modélisation et Programmation Objet (Java/UML)',
            'Administration & sécurité des SE (Unix)',
            'Réseau IP et routage (CCNA)',
            'Développement web & mobile',
            'Génie logiciel',
            'Machine Learning Fundamentals',
            'Théorie des langages'
        ];

        $articles = [];
        foreach ($subjects as $i => $subject) {
            $article = new ReferenceArticle();
            $article->setTitre("Guide de survie en $subject");
            $article->setContenu("Ce document présente les concepts clés et les meilleures pratiques pour réussir le module $subject chez ESPRIT.");
            $article->setCategorie($i % 2 === 0 ? $catIng : $catTech);
            $article->setAuteur($admin);
            $article->setCreatedAt(new \DateTime("-".rand(1, 100)." days"));
            $article->setPublished(true);
            $manager->persist($article);
            $articles[] = $article;
        }

        // 4. Programmes & Objectifs
        $classes = [];
        for ($i = 1; $i <= 70; $i++) {
            $classes[] = "3A$i";
        }

        foreach ($classes as $idx => $class) {
            if ($idx > 10) break; // Seulement 10 classes pour l'exemple
            $prog = new Programme();
            $prog->setTitre("Parcours Réussite $class");
            $prog->setDategeneration(new \DateTime('-' . rand(1, 10) . ' days'));
            $manager->persist($prog);

            $obj = new Objectif();
            $obj->setDescription("Atteindre 85% de réussite en " . $subjects[$idx % count($subjects)] . " pour la classe $class");
            $obj->setDatedebut(new \DateTime('-' . rand(1, 10) . ' days'));
            $obj->setDatefin(new \DateTime('+' . rand(10, 60) . ' days'));
            $obj->setStatut(Statutobj::EnCours);
            $obj->setUtilisateur($idx % 2 === 0 ? $prof1 : $prof2);
            $obj->setProgramme($prog);
            $manager->persist($obj);
        }

        // 5. SortieAI & PlanActions avec données réalistes
        $studentNames = ['Ahmed', 'Sarah', 'Yassine', 'Inès', 'Marwen', 'Faten', 'Nour', 'Hamza', 'Lobna', 'Tarek'];
        $statuses = [Statut::EnAttente, Statut::EnCours, Statut::Fini, Statut::Rejete];

        for ($i = 0; $i < 15; $i++) {
            $class = $classes[array_rand($classes)];
            $student = $studentNames[array_rand($studentNames)];
            $subject = $subjects[array_rand($subjects)];

            $sortie = new SortieAI();
            $sortie->setCible(Cible::Enseignant);
            $sortie->setTypeSortie(TypeSortie::Alerte);
            $sortie->setCriticite($i % 3 === 0 ? Criticite::Eleve : Criticite::Moyen);
            $sortie->setCategorieSortie(CategorieSortie::Pedagogique);
            $sortie->setContenu("Alerte Décrochage : L'étudiant $student de la classe $class présente des difficultés croissantes dans le module $subject. Une intervention est suggérée.");
            $sortie->setCreatedAt(new \DateTime("-".rand(1, 15)." days"));
            $manager->persist($sortie);

            $plan = new PlanActions();
            $plan->setDecision("Renforcement $subject - $student ($class)");
            $plan->setDescription("Séance de tutorat prévue pour $student afin de revoir les bases de $subject.");
            $plan->setDate(new \DateTime("-".rand(1, 5)." days"));
            $plan->setStatut($statuses[array_rand($statuses)]);
            $plan->setCategorie(CategorieSortie::Pedagogique);
            $plan->setSortieAI($sortie);
            $plan->addArticle($articles[array_rand($articles)]);
            $plan->setAuteur($i % 2 === 0 ? $prof1 : $prof2); // Assigning a professor
            $manager->persist($plan);
        }

        // 6. Feedback (avec réflexion pour contourner le typofix si nécessaire)
        $reflectionFeedback = new \ReflectionClass(Feedback::class);
        $propContenu = $reflectionFeedback->getProperty('contenu');
        $propContenu->setAccessible(true);

        for ($i = 1; $i <= 5; $i++) {
            $fb = new Feedback();
            $propContenu->setValue($fb, "Le système d'alertes IA pour les classes 3A est très efficace !");
            $fb->setNote(5);
            $fb->setDatefeedback(new \DateTime("-".rand(1, 20)." days"));
            $fb->setTypefeedback("SUGGESTION");
            $fb->setEtatfeedback("FERME");
            $fb->setUtilisateur($i % 2 === 0 ? $prof1 : $prof2);
            $manager->persist($fb);
        }

        // 6.1 Données Psychologiques (Humeur)
        foreach ($students as $student) {
            // Créer un profil d'apprentissage pour chaque étudiant
            $profil = new ProfilApprentissage();
            $profil->setUtilisateur($student);
            $profil->setNiveauConcentration(rand(5, 10));
            $manager->persist($profil);

            for ($j = 0; $j < 3; $j++) {
                $humeur = new Humeur();
                $val = rand(1, 10);
                $humeur->setValeurHumeur($val);
                $humeur->setCreeLe(new \DateTime("-" . ($j * 3) . " days"));
                $humeur->setFacteurPrincipal($val < 5 ? 'Stress Académique' : 'Vie Sociale');
                $humeur->setNiveauRisque($val < 4 ? 'ÉLEVÉ' : ($val < 7 ? 'MOYEN' : 'FAIBLE'));
                $humeur->setProfilApprentissage($profil);
                $manager->persist($humeur);
            }
        }

        // 6.2 Objectifs de Coaching (Module 6)
        foreach ($students as $i => $student) {
            $obj = new Objectif();
            $obj->setDescription("Maîtriser " . $subjects[$i % count($subjects)]);
            $obj->setDatedebut(new \DateTime("-10 days"));
            $obj->setDatefin(new \DateTime("+20 days"));
            $obj->setStatut(Statutobj::EnCours);
            $obj->setUtilisateur($student);
            $manager->persist($obj);
        }

        // 7. Conversations de Chat réalistes
        $conv = new Conversation();
        $conv->setUser($prof1);
        $conv->setTitre("Suivi classe 3A5 - Java");
        $conv->setCreatedAt(new \DateTime('-2 hours'));
        $manager->persist($conv);

        $msg1 = new Message();
        $msg1->setConversation($conv);
        $msg1->setRole('user');
        $msg1->setContent("Donne-moi les prédictions pour ma classe 3A5 en Java.");
        $msg1->setCreatedAt(new \DateTime('-2 hours'));
        $manager->persist($msg1);

        $msg2 = new Message();
        $msg2->setConversation($conv);
        $msg2->setRole('assistant');
        // On évite les placeholders ici
        $msg2->setContent("```json\n{\"predictions\": [{\"label\": \"Taux de réussite 3A5\", \"probability\": \"78%\", \"details\": \"Basé sur les derniers TP de Java, Ahmed et Inès sont en progression, mais Yassine nécessite un suivi.\"}]}```\nL'analyse des résultats pour la 3A5 montre une tendance positive de 78%.");
        $msg2->setCreatedAt(new \DateTime('-2 hours + 1 min'));
        $manager->persist($msg2);

        $manager->flush();

        // À AJOUTER DANS AppFixtures.php APRÈS LA SECTION 5 (SortieAI & PlanActions)

// 5.1 Données d'analyse enrichies pour les classes 3A
$classes3A = [];
for ($i = 1; $i <= 70; $i++) {
    $classes3A[] = "3A" . str_pad($i, 2, "0", STR_PAD_LEFT);
}

$prenomsTunisiens = [
    'Ahmed', 'Mohamed', 'Yassine', 'Sarra', 'Cyrine', 'Meriem', 'Omar', 'Nour', 
    'Aziz', 'Farah', 'Rania', 'Amine', 'Skander', 'Youssef', 'Malek', 'Ghassen',
    'Emna', 'Khalil', 'Imen', 'Dhia', 'Aymen', 'Selim', 'Mariem', 'Firas'
];

$modules = [
    'Java/UML' => [
        'difficultes' => [
            'héritage et polymorphisme',
            'diagrammes de classes UML',
            'gestion des exceptions',
            'collections Java',
            'design patterns',
            'relations entre classes'
        ],
        'acronym' => 'JAVA'
    ],
    'Unix' => [
        'difficultes' => [
            'scripts shell avancés',
            'gestion des droits et permissions',
            'processus et signaux',
            'administration système',
            'configuration SSH',
            'cron et automatisation'
        ],
        'acronym' => 'UNIX'
    ],
    'CCNA' => [
        'difficultes' => [
            'subnetting et VLSM',
            'configuration OSPF',
            'routage EIGRP',
            'VLAN et trunking',
            'ACL et sécurité',
            'NAT/PAT'
        ],
        'acronym' => 'CCNA'
    ],
    'Web' => [
        'difficultes' => [
            'requêtes SQL complexes',
            'AJAX et fetch API',
            'sessions PHP',
            'React hooks',
            'modélisation SGBD',
            'API REST'
        ],
        'acronym' => 'WEB'
    ],
    'Machine Learning' => [
        'difficultes' => [
            'algorithmes de régression',
            'overfitting/underfitting',
            'réseaux de neurones',
            'feature engineering',
            'évaluation de modèles',
            'gradient descent'
        ],
        'acronym' => 'ML'
    ]
];

// Génération d'analyses pour 30 classes aléatoires
for ($c = 0; $c < 30; $c++) {
    $classe = $classes3A[array_rand($classes3A)];
    $module = array_rand($modules);
    $moduleData = $modules[$module];
    
    // Génération des métriques réalistes avec variations
    $tauxReussite = rand(45, 92);
    $tendanceReussite = $tauxReussite > 70 ? 'up' : ($tauxReussite < 55 ? 'down' : 'neutral');
    
    $participation = rand(8, 19);
    $tendanceParticipation = $participation > 15 ? 'up' : ($participation < 11 ? 'down' : 'neutral');
    
    $moyenneClasse = rand(8, 16) + rand(0, 9)/10;
    $tendanceMoyenne = $moyenneClasse > 13 ? 'up' : ($moyenneClasse < 10 ? 'down' : 'neutral');
    
    // Création des étudiants en difficulté (3-5 par classe)
    $nbEtudiantsDifficiles = rand(3, 5);
    $etudiantsDifficiles = [];
    $difficultesChoisies = [];
    
    for ($e = 0; $e < $nbEtudiantsDifficiles; $e++) {
        $prenom = $prenomsTunisiens[array_rand($prenomsTunisiens)];
        $difficulte = $moduleData['difficultes'][array_rand($moduleData['difficultes'])];
        
        // Éviter les doublons d'étudiants dans la même classe
        while (in_array($prenom, $etudiantsDifficiles)) {
            $prenom = $prenomsTunisiens[array_rand($prenomsTunisiens)];
        }
        
        $etudiantsDifficiles[] = $prenom;
        $difficultesChoisies[] = $difficulte;
    }
    
    // Étudiant en tête (différent des difficiles)
    $teteClasse = $prenomsTunisiens[array_rand($prenomsTunisiens)];
    while (in_array($teteClasse, $etudiantsDifficiles)) {
        $teteClasse = $prenomsTunisiens[array_rand($prenomsTunisiens)];
    }
    
    // Construction du message d'analyse
    $analyseMessage = "";
    
    // Cas spécial pour 3A22 en ML (demandé dans l'exemple)
    if ($classe === '3A22' && $module === 'Machine Learning') {
        $analyseMessage = "Analyse approfondie pour la classe 3A22 en Machine Learning Fundamentals :\n\n";
        $analyseMessage .= "La classe présente une performance hétérogène avec un taux de réussite global de 68%. ";
        $analyseMessage .= "Mohamed se distingue particulièrement avec une moyenne de 17.5/20, excellant dans les implémentations pratiques des algorithmes de classification. ";
        $analyseMessage .= "Cependant, Sarra rencontre des difficultés significatives avec les algorithmes de régression (score de 9/20 aux TP), notamment sur la compréhension de la descente de gradient et l'évaluation des modèles. ";
        $analyseMessage .= "La participation en cours reste satisfaisante (15/20), mais 4 autres étudiants (Yassine, Meriem, Aziz et Emna) montrent des signes de décrochage sur les parties théoriques du cours. ";
        $analyseMessage .= "Recommandation : Organiser une séance de rattrapage ciblée sur la régression linéaire et polynomiale, avec des exercices pratiques supplémentaires.\n\n";
        
        $jsonData = "```json\n";
        $jsonData .= "{\n";
        $jsonData .= "  \"metrics\": [\n";
        $jsonData .= "    {\"label\": \"Taux de réussite\", \"value\": \"68\", \"unit\": \"%\", \"trend\": \"neutral\"},\n";
        $jsonData .= "    {\"label\": \"Participation\", \"value\": \"15\", \"unit\": \"/20\", \"trend\": \"up\"},\n";
        $jsonData .= "    {\"label\": \"Moyenne classe\", \"value\": \"12.4\", \"unit\": \"/20\", \"trend\": \"down\"},\n";
        $jsonData .= "    {\"label\": \"TP rendus\", \"value\": \"87\", \"unit\": \"%\", \"trend\": \"up\"}\n";
        $jsonData .= "  ],\n";
        $jsonData .= "  \"alerts\": [\n";
        $jsonData .= "    {\"level\": \"high\", \"message\": \"Sarra en difficulté sévère sur les algorithmes de régression\"},\n";
        $jsonData .= "    {\"level\": \"medium\", \"message\": \"4 étudiants en risque de décrochage sur la partie théorique\"}\n";
        $jsonData .= "  ],\n";
        $jsonData .= "  \"predictions\": [\n";
        $jsonData .= "    {\"label\": \"Réussite à l'examen\", \"probability\": \"74%\", \"details\": \"Basé sur les performances actuelles et la progression\"},\n";
        $jsonData .= "    {\"label\": \"Moyenne finale estimée\", \"probability\": \"11.8/20\", \"details\": \"Écart-type de 3.2\"}\n";
        $jsonData .= "  ],\n";
        $jsonData .= "  \"decisions\": [\n";
        $jsonData .= "    {\"action\": \"Session de rattrapage régression - Sarra + groupe\", \"priority\": \"high\"},\n";
        $jsonData .= "    {\"action\": \"Exercices supplémentaires sur la descente de gradient\", \"priority\": \"medium\"},\n";
        $jsonData .= "    {\"action\": \"Monitorer Mohamed pour préparation concours\", \"priority\": \"low\"}\n";
        $jsonData .= "  ]\n";
        $jsonData .= "}\n";
        $jsonData .= "```";
        
        $analyseMessage .= $jsonData;
    } else {
        // Génération standard pour les autres classes
        $analyseMessage = "Analyse de performance - Classe $classe en $module :\n\n";
        $analyseMessage .= "Taux de réussite global : $tauxReussite% (tendance " . ($tendanceReussite === 'up' ? '📈' : ($tendanceReussite === 'down' ? '📉' : '➡️')) . "). ";
        $analyseMessage .= "Participation moyenne : $participation/20. ";
        $analyseMessage .= "Moyenne de classe : " . number_format($moyenneClasse, 1) . "/20.\n\n";
        
        $analyseMessage .= "Étudiants en difficulté :\n";
        foreach ($etudiantsDifficiles as $index => $etudiant) {
            $analyseMessage .= "- $etudiant : difficultés avec " . $difficultesChoisies[$index] . "\n";
        }
        
        $analyseMessage .= "\nÉtudiant tête de classe : $teteClasse avec une moyenne de " . rand(16, 19) . "/20.\n\n";
        
        // Construction du JSON pour cette classe
        $niveauAlerte = $tauxReussite < 60 ? 'high' : ($tauxReussite < 75 ? 'medium' : 'low');
        $messageAlerte = $niveauAlerte === 'high' 
            ? "Risque élevé d'échec collectif - intervention urgente recommandée"
            : ($niveauAlerte === 'medium' 
                ? "Surveillance renforcée nécessaire pour " . $nbEtudiantsDifficiles . " étudiants"
                : "Situation sous contrôle, suivi individuel des cas difficiles");
        
        $jsonData = "```json\n";
        $jsonData .= "{\n";
        $jsonData .= "  \"metrics\": [\n";
        $jsonData .= "    {\"label\": \"Taux de réussite\", \"value\": \"$tauxReussite\", \"unit\": \"%\", \"trend\": \"$tendanceReussite\"},\n";
        $jsonData .= "    {\"label\": \"Participation\", \"value\": \"$participation\", \"unit\": \"/20\", \"trend\": \"$tendanceParticipation\"},\n";
        $jsonData .= "    {\"label\": \"Moyenne classe\", \"value\": \"" . number_format($moyenneClasse, 1) . "\", \"unit\": \"/20\", \"trend\": \"$tendanceMoyenne\"}\n";
        $jsonData .= "  ],\n";
        $jsonData .= "  \"alerts\": [\n";
        $jsonData .= "    {\"level\": \"$niveauAlerte\", \"message\": \"$messageAlerte\"},\n";
        
        // Ajout d'alertes spécifiques pour les difficultés
        $firstAlerte = true;
        foreach ($etudiantsDifficiles as $index => $etudiant) {
            $jsonData .= ($index === 0 && $firstAlerte ? '' : ',') . "\n";
            $jsonData .= "    {\"level\": \"medium\", \"message\": \"$etudiant : " . $difficultesChoisies[$index] . "\"}";
            $firstAlerte = false;
        }
        $jsonData .= "\n  ],\n";
        
        $jsonData .= "  \"predictions\": [\n";
        $probReussite = min(95, $tauxReussite + rand(-10, 15));
        $probEchec = 100 - $probReussite;
        $jsonData .= "    {\"label\": \"Réussite à l'examen\", \"probability\": \"" . $probReussite . "%\", \"details\": \"Progression " . ($probReussite > $tauxReussite ? "positive" : "stable") . "\"},\n";
        $jsonData .= "    {\"label\": \"Risque d'échec\", \"probability\": \"" . $probEchec . "%\", \"details\": \"Concentré sur " . $nbEtudiantsDifficiles . " étudiants\"}\n";
        $jsonData .= "  ],\n";
        
        $jsonData .= "  \"decisions\": [\n";
        $jsonData .= "    {\"action\": \"Tutorat ciblé pour " . implode(', ', array_slice($etudiantsDifficiles, 0, 3)) . "\", \"priority\": \"" . ($niveauAlerte === 'high' ? 'high' : 'medium') . "\"},\n";
        
        $action2 = $tauxReussite < 65 
            ? "Révision générale du module $module" 
            : "Exercices supplémentaires sur " . $difficultesChoisies[0];
        $jsonData .= "    {\"action\": \"$action2\", \"priority\": \"medium\"}\n";
        $jsonData .= "  ]\n";
        $jsonData .= "}\n";
        $jsonData .= "```";
        
        $analyseMessage .= $jsonData;
    }
    
    // Création d'une SortieAI pour cette analyse
    $sortieAnalyse = new SortieAI();
    $sortieAnalyse->setCible(Cible::Enseignant);
    $sortieAnalyse->setTypeSortie(TypeSortie::Analyse);
    $sortieAnalyse->setCriticite(
        $tauxReussite < 50 ? Criticite::Eleve : 
        ($tauxReussite < 70 ? Criticite::Moyen : Criticite::Faible)
    );
    $sortieAnalyse->setCategorieSortie(CategorieSortie::Pedagogique);
    $sortieAnalyse->setContenu($analyseMessage);
    $sortieAnalyse->setCreatedAt(new \DateTime("-" . rand(1, 7) . " days"));
    $manager->persist($sortieAnalyse);
    
    // Création d'un PlanActions associé
    $planAnalyse = new PlanActions();
    $planAnalyse->setDecision("Plan d'action - $classe ($module)");
    $planAnalyse->setDescription(substr($analyseMessage, 0, 200) . "...");
    $planAnalyse->setDate(new \DateTime("-" . rand(0, 3) . " days"));
    $planAnalyse->setStatut(Statut::EnCours);
    $planAnalyse->setCategorie(CategorieSortie::Pedagogique);
    $planAnalyse->setSortieAI($sortieAnalyse);
    if (!empty($articles)) {
        $planAnalyse->addArticle($articles[array_rand($articles)]);
    }
    $planAnalyse->setAuteur($c % 2 === 0 ? $prof1 : $prof2); // Assigning a professor
    $manager->persist($planAnalyse);
}

// 5.2 Conversations de chat avec analyses détaillées
for ($chatIdx = 0; $chatIdx < 15; $chatIdx++) {
    $enseignant = $chatIdx % 2 === 0 ? $prof1 : $prof2;
    $classeChat = $classes3A[array_rand($classes3A)];
    $moduleChat = array_rand($modules);
    
    $conversation = new Conversation();
    $conversation->setUser($enseignant);
    $conversation->setTitre("Analyse prédictive - $classeChat en $moduleChat");
    $conversation->setCreatedAt(new \DateTime("-" . rand(1, 30) . " days"));
    $manager->persist($conversation);
    
    // Message utilisateur
    $msgUser = new Message();
    $msgUser->setConversation($conversation);
    $msgUser->setRole('user');
    $msgUser->setContent("Peux-tu me donner une analyse prédictive pour ma classe $classeChat en $moduleChat ?");
    $msgUser->setCreatedAt($conversation->getCreatedAt());
    $manager->persist($msgUser);
    
    // Message assistant avec JSON intégré
    $msgAssistant = new Message();
    $msgAssistant->setConversation($conversation);
    $msgAssistant->setRole('assistant');
    
    $jsonPredictif = "```json\n";
    $jsonPredictif .= "{\n";
    $jsonPredictif .= "  \"predictions\": [\n";
    
    $predictionCount = rand(3, 5);
    for ($p = 0; $p < $predictionCount; $p++) {
        $predictionTypes = [
            "Taux de réussite final",
            "Moyenne attendue",
            "Étudiants à risque",
            "Performance examen",
            "Progression attendue",
            "Note projet"
        ];
        $prob = rand(55, 95);
        $jsonPredictif .= "    " . ($p > 0 ? "," : "") . "{\"label\": \"" . $predictionTypes[$p % count($predictionTypes)] . "\", \"probability\": \"" . $prob . "%\", \"details\": \"Basé sur l'historique des " . rand(3, 8) . " dernières évaluations\"}\n";
    }
    
    $jsonPredictif .= "  ],\n";
    $jsonPredictif .= "  \"recommandations\": [\n";
    
    $recoCount = rand(2, 4);
    for ($r = 0; $r < $recoCount; $r++) {
        $recos = [
            "Renforcer les séances de TD sur les concepts fondamentaux",
            "Organiser des groupes de niveau",
            "Prévoir des exercices supplémentaires",
            "Session de rattrapage intensif",
            "Suivi individualisé pour les cas difficiles",
            "Préparation aux examens anticipée"
        ];
        $jsonPredictif .= "    " . ($r > 0 ? "," : "") . "{\"action\": \"" . $recos[$r % count($recos)] . "\", \"priorite\": \"" . (rand(1, 10) > 7 ? "haute" : "moyenne") . "\"}\n";
    }
    
    $jsonPredictif .= "  ]\n";
    $jsonPredictif .= "}\n";
    $jsonPredictif .= "```\n\n";
    
    $jsonPredictif .= "Voici l'analyse prédictive pour la classe $classeChat en $moduleChat. Les prédictions sont basées sur les données des 3 derniers mois. ";
    $jsonPredictif .= "Je note particulièrement que " . rand(2, 5) . " étudiants nécessitent une attention spécifique. ";
    $jsonPredictif .= "Souhaitez-vous que je détaille les actions recommandées pour chaque cas ?";
    
    $msgAssistant->setContent($jsonPredictif);
    $msgAssistant->setCreatedAt((clone $conversation->getCreatedAt())->modify('+1 minute'));
    $manager->persist($msgAssistant);
}

        $manager->flush();
    }
}
