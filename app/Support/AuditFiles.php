<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditFiles
{
    public static function csv(Builder $query): StreamedResponse
    {
        $filename = 'volunteerhub-audit-logs-'.now(config('audit.display_timezone', 'Asia/Manila'))->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Log ID', 'Date and time', 'User name', 'User role', 'Action', 'Module', 'Description', 'Status', 'Affected record', 'Previous values', 'New values', 'IP address']);

            foreach ($query->clone()->cursor() as $log) {
                fputcsv($handle, self::row($log));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function pdf(Builder $query): StreamedResponse
    {
        $filename = 'volunteerhub-audit-logs-'.now(config('audit.display_timezone', 'Asia/Manila'))->format('Y-m-d').'.pdf';
        $total = (clone $query)->count();
        $logs = $query->clone()->orderByDesc('created_at')->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($logs, $total) {
            echo AuditPdf::make($logs, $total);
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public static function excel(Builder $query): StreamedResponse
    {
        $filename = 'volunteerhub-audit-logs-'.now(config('audit.display_timezone', 'Asia/Manila'))->format('Y-m-d').'.xls';

        return response()->streamDownload(function () use ($query) {
            echo '<?xml version="1.0" encoding="UTF-8"?>'
                .'<?mso-application progid="Excel.Sheet"?>'
                .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Audit Logs"><Table>';

            $headers = ['Log ID', 'Date and time', 'User name', 'User role', 'Action', 'Module', 'Description', 'Status', 'Affected record', 'Previous values', 'New values', 'IP address'];
            echo self::xmlRow($headers);

            foreach ($query->clone()->orderByDesc('created_at')->orderByDesc('id')->cursor() as $log) {
                echo self::xmlRow(self::row($log));
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private static function row(AuditLog $log): array
    {
        return [
            $log->id,
            AuditTrail::localTime($log->created_at),
            $log->actor_name,
            AuditTrail::roleLabel($log->actor_role),
            $log->action,
            $log->module,
            $log->description,
            $log->status === 'failed' ? 'Failed' : 'Success',
            $log->subject_label ?? '',
            $log->previous_values ? json_encode($log->previous_values, JSON_UNESCAPED_UNICODE) : '',
            $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
            $log->ip_address ?? '',
        ];
    }

    private static function xmlRow(array $values): string
    {
        $xml = '<Row>';
        foreach ($values as $value) {
            $xml .= '<Cell><Data ss:Type="String">'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        }

        return $xml.'</Row>';
    }
}
