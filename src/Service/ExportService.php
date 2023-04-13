<?php

namespace App\Service;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use phpDocumentor\Reflection\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * @param array $healthRecords
     * @param string $fileName
     * @return string
     */
    public function exportHealthRecords(array $healthRecords, string $fileName): string
    {
        try {
            dump(count($healthRecords));
            $csv = fopen('public/exports/' . $fileName, 'w+');
            fputcsv($csv,
                [
                    'veterinarian',
                    'pet',
                    'start time',
                    'finish time',
                    'notified',
                    'is made by vet'
                ]
            );
            foreach ($healthRecords as $healthRecord){
                fputcsv($csv,
                    [
                        $healthRecord["vet_id"],
                        $healthRecord["pet_id"],
                        $healthRecord["started_at"],
                        $healthRecord["finished_at"],
                        $healthRecord["notified"]==0 ? 'not notified ' : 'notified',
                        $healthRecord["made_by_vet"]==0 ? 'scheduled' : 'made by vet'
                    ]
                );
            }

        } catch (Exception $e) {
            dump($e);
        }
        return 'done';
    }
}