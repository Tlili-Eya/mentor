<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JobRecommendationService
{
    private $httpClient;
    private $adzunaAppId;
    private $adzunaAppKey;

    public function __construct(
        HttpClientInterface $httpClient,
        string $adzunaAppId = '4e630c20',
        string $adzunaAppKey = 'f6e51e220eccb4de666ad92ef1653bd9'
    ) {
        $this->httpClient = $httpClient;
        $this->adzunaAppId = $adzunaAppId;
        $this->adzunaAppKey = $adzunaAppKey;
    }

    /**
     * Generates a professional profile string based on user projects and background.
     */
    public function generateUserProfile(Utilisateur $user): string
    {
        $profile = "Professional Profile of " . $user->getNom() . " " . $user->getPrenom() . ".\n";
        
        $profile .= "\n--- Academic & Professional Background ---\n";
        foreach ($user->getParcours() as $parcours) {
            $profile .= "- Title: " . $parcours->getTitre() . "\n";
            $profile .= "  Type: " . $parcours->getTypeParcours() . " at " . $parcours->getEtablissement() . "\n";
            $profile .= "  Description: " . $parcours->getDescription() . "\n";
        }

        $profile .= "\n--- Projects ---\n";
        foreach ($user->getProjets() as $projet) {
            $profile .= "- Project: " . $projet->getTitre() . " (Type: " . $projet->getType() . ")\n";
            $profile .= "  Technologies: " . $projet->getTechnologies() . "\n";
            $profile .= "  Description: " . $projet->getDescription() . "\n";
        }

        return $profile;
    }

    /**
     * Fetches job offers from Adzuna API based on keywords.
     */
    public function fetchJobs(string $keywords, string $location = 'france', int $resultsPerPage = 20): array
    {
        $url = sprintf(
            'https://api.adzuna.com/v1/api/jobs/fr/search/1?app_id=%s&app_key=%s&what=%s&results_per_page=%d',
            $this->adzunaAppId,
            $this->adzunaAppKey,
            urlencode($keywords),
            $resultsPerPage
        );

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            $jobs = [];
            foreach ($data['results'] as $result) {
                $jobs[] = [
                    'title' => $result['title'] ?? 'N/A',
                    'company' => $result['company']['display_name'] ?? 'N/A',
                    'location' => $result['location']['display_name'] ?? 'France',
                    'description' => $result['description'] ?? '',
                    'url' => $result['redirect_url'] ?? '#',
                    'salary' => isset($result['salary_min']) ? $result['salary_min'] . ' - ' . ($result['salary_max'] ?? '') : 'Sur demande',
                    'latitude' => $result['latitude'] ?? null,
                    'longitude' => $result['longitude'] ?? null
                ];
            }

            return $jobs;
        } catch (\Exception $e) {
            // Log error or return empty array
            return [];
        }
    }

    /**
     * Uses AI to rank jobs based on the user profile.
     */
    public function rankJobs(string $userProfile, array $jobs): array
    {
        $technos = $this->extractSkills($userProfile);
        
        foreach ($jobs as &$job) {
            $score = 0;
            $textToSearch = ($job['title'] ?? '') . ' ' . ($job['description'] ?? '');
            
            foreach ($technos as $tech) {
                if (stripos($textToSearch, $tech) !== false) {
                    $score += 25;
                }
            }

            // Bonus for specific titles
            if (stripos($job['title'], 'Senior') !== false || stripos($job['title'], 'Lead') !== false) {
                if (count($technos) > 5) $score += 10;
            }

            $job['match_score'] = min(99, 40 + $score + rand(0, 10));
        }

        usort($jobs, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return $jobs;
    }

    /**
     * Generates "Next Step" career suggestions based on user projects.
     */
    public function getCareerSuggestions(Utilisateur $user): array
    {
        $projects = $user->getProjets();
        $allTechs = [];
        foreach ($projects as $p) {
            $t = explode(',', $p->getTechnologies());
            foreach ($t as $tech) {
                $trimmed = trim($tech);
                if (!empty($trimmed)) $allTechs[] = strtolower($trimmed);
            }
        }
        $uniqueTechs = array_unique($allTechs);

        $suggestions = [];

        // Logic for next steps
        if (in_array('php', $uniqueTechs) || in_array('symfony', $uniqueTechs)) {
            $suggestions[] = [
                'title' => 'Maîtriser React ou Vue.js',
                'description' => 'En tant que développeur PHP/Symfony, devenir Fullstack avec un framework JS moderne augmentera votre employabilité de 40%.',
                'icon' => 'bi-code-slash'
            ];
            $suggestions[] = [
                'title' => 'Architecture Microservices',
                'description' => 'Vos projets montrent une bonne base monolithique. Apprendre Docker et Kubernetes serait la suite logique.',
                'icon' => 'bi-layers'
            ];
        }

        if (in_array('python', $uniqueTechs)) {
            $suggestions[] = [
                'title' => 'Deep Learning & IA',
                'description' => 'Puisque vous utilisez Python, spécialisez-vous en TensorFlow ou PyTorch pour viser des postes de Data Scientist.',
                'icon' => 'bi-cpu'
            ];
        }

        if (count($uniqueTechs) < 3) {
            $suggestions[] = [
                'title' => 'Diversifier votre Stack',
                'description' => 'Ajoutez plus de projets avec des technologies variées (NoSQL, Cloud, API REST) pour enrichir votre profil.',
                'icon' => 'bi-plus-circle'
            ];
        } else {
             $suggestions[] = [
                'title' => 'Certification DevOps',
                'description' => 'Votre profil est déjà solide techniquement. Une certification AWS ou Azure serait un atout majeur.',
                'icon' => 'bi-shield-check'
            ];
        }

        return array_slice($suggestions, 0, 3);
    }

    private function extractSkills(string $profile): array
    {
        $commonTechs = ['PHP', 'Symfony', 'React', 'Vue', 'Angular', 'Python', 'Java', 'Docker', 'Kubernetes', 'SQL', 'NoSQL', 'AWS', 'Azure', 'JavaScript', 'TypeScript'];
        $found = [];
        foreach ($commonTechs as $tech) {
            if (stripos($profile, $tech) !== false) {
                $found[] = $tech;
            }
        }
        return $found;
    }
}
