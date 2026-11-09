<?php

namespace App\Service;

use League\Csv\Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class ExportService
{
    // outside public/: the CSV holds health records and is only mailed to the admin
    private readonly string $dir;

    public function __construct(#[Autowire('%kernel.project_dir%/var/exports')] string $dir)
    {
        $this->dir = rtrim($dir, '/') . '/';
    }

    /**
     * @param array $healthRecords
     * @param string $fileName
     * @return string
     */
    public function exportHealthRecords(array $healthRecords, string $fileName): string
    {
        try {
            new Filesystem()->mkdir($this->dir);
            $csv = fopen($this->dir . $fileName, 'w+');
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
                ],
                escape: '\\'
            );
            foreach ($healthRecords as $healthRecord) {
                fputcsv($csv,
                    [
                        $healthRecord["id"],
                        $healthRecord["vetFirstName"],
                        $healthRecord["petName"],
                        $healthRecord["examName"],
                        $healthRecord["startedAt"]->format('Y-m-d H:i:s'),
                        $healthRecord["finishedAt"]->format('Y-m-d H:i:s'),
                        $healthRecord["notifiedWeekBefore"] == 0 ? 'not notified ' : 'notified',
                        $healthRecord["notifiedDayBefore"] == 0 ? 'not notified ' : 'notified',
                        $healthRecord["madeByVet"] ? 'made by vet' : 'scheduled'
                    ],
                    escape: '\\'
                );
            }
            fclose($csv);

            return $this->dir . $fileName;
        }
        catch (Exception $e) {
            error_log($e->getMessage());
        }
        return "";
    }
}