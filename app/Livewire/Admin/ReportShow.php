<?php

namespace App\Livewire\Admin;

use App\Enums\ReportType;
use App\Support\BakeryReports;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Report')]
class ReportShow extends Component
{
    public string $report;

    #[Url]
    public string $month = '';

    public function mount(string $report): void
    {
        abort_unless(ReportType::tryFrom($report) !== null, 404);

        $this->report = $report;

        if ($this->month === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function updatedMonth(string $value): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $value)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function render(): View
    {
        $type = ReportType::from($this->report);
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $payload = BakeryReports::build($type, $month);

        return view('livewire.admin.report-show', [
            'payload' => $payload,
            'downloadUrl' => route('admin.reports.download', [
                'report' => $type->value,
                'month' => $this->month,
            ]),
        ]);
    }
}
