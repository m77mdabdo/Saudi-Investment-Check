<?php

namespace App\Exports;

use App\Http\Controllers\Admin\RegistrationController;
use App\Models\EventRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Every cell is bound as a string. Without this the default binder reads
 * "+966512345678" as a number and writes "966512345678", silently dropping the
 * leading + and with it the country code.
 */
class RegistrationsExport extends StringValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(
        protected array $filters,
        protected RegistrationController $controller,
    ) {}

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->controller->query($this->filters)
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Phone'];
    }

    /** @param EventRegistration $row */
    public function map($row): array
    {
        // Three columns only. The photo is viewable in the dashboard and is
        // deliberately not exported.
        return [
            $row->name,
            $row->email,
            $row->phone,
        ];
    }
}
