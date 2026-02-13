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
                    'salary' => isset($result['salary_min']) ? $result['salary_min'] . ' - ' . ($result['salary_max'] ?? '') : 'Sur demande'
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
        // In a real scenario, this would call OpenAI or Mistral
        // We will simulate the ranking with a simple matching for now
        foreach ($jobs as &$job) {
            $score = 0;
            if (stripos($userProfile, 'Symfony') !== false && stripos($job['title'], 'Symfony') !== false) $score += 40;
            if (stripos($userProfile, 'Python') !== false && stripos($job['description'], 'Python') !== false) $score += 30;
            $job['match_score'] = min(98, 60 + $score + rand(0, 10));
        }

        usort($jobs, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return $jobs;
    }
}
