<?php
// src/Service/ExcelExportService.php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExcelExportService
{
    public function exportToExcel(array $data, string $filename, array $headers = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $header);
        }

        // Données
        $row = 2;
        foreach ($data as $item) {
            $col = 1;
            foreach ($item as $value) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $value);
            }
            $row++;
        }

        // Style des en-têtes
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF102C59']
            ],
            'color' => ['argb' => 'FFFFFFFF']
        ];
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Créer le fichier
        $writer = new Xlsx($spreadsheet);
        
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        // Créer la réponse
        $response = new Response(file_get_contents($tempFile));
        
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '.xlsx'
        );
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        
        unlink($tempFile);

        return $response;
    }
}