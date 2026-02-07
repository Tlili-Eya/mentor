<?php

namespace App\Service;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Service pour générer des exports PDF des feedbacks traités
 */
class PdfExportService
{
    /**
     * Génère un PDF pour un feedback traité spécifique
     */
    public function generateFeedbackPdf(Feedback $feedback): string
    {
        // Configurer Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->generateFeedbackHtml($feedback);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Définir la taille du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Retourner le PDF en string
        return $dompdf->output();
    }
    
    /**
     * Génère un PDF pour tous les feedbacks traités
     */
    public function generateAllFeedbacksPdf(FeedbackRepository $feedbackRepo): string
    {
        // Récupérer tous les feedbacks traités
        $feedbacks = $feedbackRepo->findBy(
            ['etatfeedback' => 'traite'],
            ['datefeedback' => 'DESC']
        );
        
        // Configurer Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->generateAllFeedbacksHtml($feedbacks);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Définir la taille du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Retourner le PDF en string
        return $dompdf->output();
    }
    
    /**
     * Génère le HTML pour un feedback unique
     */
    private function generateFeedbackHtml(Feedback $feedback): string
    {
        $user = $feedback->getUtilisateur();
        $traitement = $feedback->getTraitement();
        
        $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Feedback #' . $feedback->getId() . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28pt;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 12pt;
            opacity: 0.9;
        }
        
        .container {
            padding: 0 40px;
        }
        
        .section {
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 8px 15px 8px 0;
            width: 35%;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-danger {
            background-color: #dc3545;
        }
        
        .badge-info {
            background-color: #17a2b8;
        }
        
        .badge-primary {
            background-color: #667eea;
        }
        
        .content-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .stars {
            color: #ffc107;
            font-size: 14pt;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #888;
            font-size: 9pt;
        }
        
        .separator {
            height: 2px;
            background: linear-gradient(to right, #667eea, #764ba2);
            margin: 25px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport de Feedback</h1>
        <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
    </div>
    
    <div class="container">
        <!-- Informations du Feedback -->
        <div class="section">
            <div class="section-title">📋 Informations du Feedback</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">ID Feedback :</div>
                    <div class="info-value"><strong>#' . $feedback->getId() . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date de soumission :</div>
                    <div class="info-value">' . $feedback->getDatefeedback()->format('d/m/Y à H:i') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Type :</div>
                    <div class="info-value">';
        
        // Badge type
        switch ($feedback->getTypefeedback()) {
            case 'suggestion':
                $html .= '<span class="badge badge-info">💡 Suggestion</span>';
                break;
            case 'probleme':
                $html .= '<span class="badge badge-danger">⚠️ Problème</span>';
                break;
            case 'satisfaction':
                $html .= '<span class="badge badge-success">😊 Satisfaction</span>';
                break;
            default:
                $html .= '<span class="badge badge-primary">' . ucfirst($feedback->getTypefeedback()) . '</span>';
        }
        
        $html .= '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Note :</div>
                    <div class="info-value">
                        <span class="stars">';
        
        // Étoiles
        for ($i = 1; $i <= 5; $i++) {
            $html .= $i <= $feedback->getNote() ? '★' : '☆';
        }
        
        $html .= '</span> (' . $feedback->getNote() . '/5)
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">État :</div>
                    <div class="info-value">
                        <span class="badge badge-success">✓ Traité</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="separator"></div>
        
        <!-- Informations Utilisateur -->
        <div class="section">
            <div class="section-title">👤 Utilisateur</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nom complet :</div>
                    <div class="info-value"><strong>' . htmlspecialchars($user->getNom() . ' ' . $user->getPrenom()) . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value">' . htmlspecialchars($user->getEmail()) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">ID Utilisateur :</div>
                    <div class="info-value">#' . $user->getId() . '</div>
                </div>
            </div>
        </div>
        
        <div class="separator"></div>
        
        <!-- Message -->
        <div class="section">
            <div class="section-title">💬 Message de l\'utilisateur</div>
            <div class="content-box">
                ' . nl2br(htmlspecialchars($feedback->getContenu())) . '
            </div>
        </div>
        
        <div class="separator"></div>';
        
        // Informations du Traitement
        if ($traitement) {
            $html .= '
        <div class="section">
            <div class="section-title">⚙️ Traitement Effectué</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Type de traitement :</div>
                    <div class="info-value">
                        <span class="badge badge-primary">' . ucfirst(str_replace('_', ' ', $traitement->getTypetraitement())) . '</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date du traitement :</div>
                    <div class="info-value">' . $traitement->getDatetraitement()->format('d/m/Y à H:i') . '</div>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <strong>Décision / Réponse :</strong>
                <div class="content-box">
                    ' . nl2br(htmlspecialchars($traitement->getDescription())) . '
                </div>
            </div>
        </div>';
        }
        
        $html .= '
        
        <div class="footer">
            <p>Ce document a été généré automatiquement par le système de gestion des feedbacks</p>
            <p>© ' . date('Y') . ' - Mentor Platform - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Génère le HTML pour tous les feedbacks
     */
    private function generateAllFeedbacksHtml(array $feedbacks): string
    {
        $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Global des Feedbacks</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10pt;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .header h1 {
            font-size: 24pt;
            margin-bottom: 8px;
        }
        
        .stats {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stats strong {
            font-size: 18pt;
            color: #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background-color: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 9pt;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9pt;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
        }
        
        .badge-danger {
            background-color: #dc3545;
        }
        
        .badge-info {
            background-color: #17a2b8;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #888;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Global des Feedbacks Traités</h1>
        <p>Généré le ' . date('d/m/Y à H:i') . '</p>
    </div>
    
    <div class="stats">
        <strong>' . count($feedbacks) . '</strong> feedback(s) traité(s)
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Type</th>
                <th>Note</th>
                <th>Traitement</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($feedbacks as $feedback) {
            $user = $feedback->getUtilisateur();
            $traitement = $feedback->getTraitement();
            
            $html .= '
            <tr>
                <td><strong>#' . $feedback->getId() . '</strong></td>
                <td>' . $feedback->getDatefeedback()->format('d/m/Y') . '</td>
                <td>' . htmlspecialchars($user->getNom() . ' ' . $user->getPrenom()) . '</td>
                <td>';
            
            // Badge type
            switch ($feedback->getTypefeedback()) {
                case 'suggestion':
                    $html .= '<span class="badge badge-info">Suggestion</span>';
                    break;
                case 'probleme':
                    $html .= '<span class="badge badge-danger">Problème</span>';
                    break;
                case 'satisfaction':
                    $html .= '<span class="badge badge-success">Satisfaction</span>';
                    break;
            }
            
            $html .= '</td>
                <td>' . $feedback->getNote() . '/5</td>
                <td>' . ($traitement ? ucfirst(str_replace('_', ' ', $traitement->getTypetraitement())) : '-') . '</td>
            </tr>';
        }
        
        $html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>© ' . date('Y') . ' - Mentor Platform - Rapport confidentiel</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}
