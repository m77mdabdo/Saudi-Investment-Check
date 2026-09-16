<?php

namespace App\Exports;

use App\Models\Lead;
use App\Services\LeadQuery;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected array $filters,
        protected LeadQuery $leadQuery,
    ) {}

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->leadQuery->results($this->filters)
            ->with(['event', 'qrSource', 'owner'])
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Name', 'Company', 'WhatsApp', 'Email', 'Consent',
            'Company Stage', 'Sector', 'Sector Other', 'Saudi Goal', 'Saudi Traction',
            'Timeline', 'Budget', 'Operational Readiness', 'Main Question',
            'Score', 'Max Score', 'Result', 'Classification', 'Sales Status', 'Assigned To',
            'Source', 'QR Source', 'Event',
            'UTM Source', 'UTM Medium', 'UTM Campaign', 'UTM Content',
            'Device', 'Browser', 'Created At',
        ];
    }

    /** @param Lead $lead */
    public function map($lead): array
    {
        $a = $lead->answers_summary ?? [];

        return [
            $lead->name,
            $lead->company,
            $lead->whatsapp,
            $lead->email,
            $lead->consent ? 'Yes' : 'No',
            $a['company_stage'] ?? '',
            $a['sector'] ?? '',
            $a['sector_other'] ?? '',
            $a['saudi_goal'] ?? '',
            $a['saudi_traction'] ?? '',
            $a['timeline'] ?? '',
            $a['budget'] ?? '',
            $a['operational_readiness'] ?? '',
            $a['main_question'] ?? '',
            $lead->score,
            $lead->max_score,
            $lead->result_key,
            $lead->classification,
            $lead->sales_status,
            $lead->owner?->name,
            $lead->source,
            $lead->qrSource?->name,
            $lead->event?->name,
            $lead->utm_source,
            $lead->utm_medium,
            $lead->utm_campaign,
            $lead->utm_content,
            $lead->device,
            $lead->browser,
            $lead->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
