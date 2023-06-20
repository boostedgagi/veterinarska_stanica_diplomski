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
    public const PATH = 'public/exports/';

    /**
     * @param array $healthRecords
     * @param string $fileName
     * @return string
     */
    public function exportHealthRecords(array $healthRecords, string $fileName): string
    {
        try
            {
            $csv = fopen( self::PATH . $fileName, 'w+');
            fputcsv($csv,
                [
                    'id',
                    'vet name',
                    'pet name',
                    'exam name',
                    'start time',
                    'finish time',
                    'notified_week_before',
                    'notified_day_before',
                    'is made by vet'
                ]
            );
            foreach ($healthRecords as $healthRecord)
                {
                fputcsv($csv,
                    [
                        $healthRecord["id"],
                        $healthRecord["vetFirstName"],
                        $healthRecord["petName"],
                        $healthRecord["examName"],
                        $healthRecord["startedAt"]->format('Y-m-d H:i:s'),
                        $healthRecord["finishedAt"]->format('Y-m-d H:i:s'),
                        $healthRecord["notifiedWeekBefore"]==0 ? 'not notified ' : 'notified',
                        $healthRecord["notifiedDayBefore"]==0 ? 'not notified ' : 'notified',
                        $healthRecord["madeByVet"] ? 'made by vet' : 'scheduled'
                    ]
                );
                }
            return self::PATH . $fileName;
            }
        catch (Exception $e)
            {
            error_log($e->getMessage());
            }
        return 'Error occurred. Try again later.';
    }
}