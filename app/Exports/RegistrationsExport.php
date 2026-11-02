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
 * Every cell is bound as a string. The default binder reads "+966512345678" as
 * a number and drops the leading +, taking the country code with it.
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
        // Three columns only. The photo stays in the dashboard.
        return [
            $row->name,
            $row->email,
            $row->phone,
        ];
    }
}
