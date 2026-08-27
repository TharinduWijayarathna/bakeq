<?php

namespace App\Livewire\Admin;

use App\Support\BakeryReports;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Reports')]
class ReportsIndex extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
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
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $overview = BakeryReports::monthOverview($month);

        return view('livewire.admin.reports-index', [
            'overview' => [
                ...$overview,
                'ingredient_cost_formatted' => Money::format($overview['ingredient_cost']),
                'revenue_formatted' => Money::format($overview['revenue']),
                'paid_earnings_formatted' => Money::format($overview['paid_earnings']),
                'outstanding_formatted' => Money::format($overview['outstanding']),
                'cogs_formatted' => Money::format($overview['cogs']),
                'waste_cost_formatted' => Money::format($overview['waste_cost']),
                'gross_profit_formatted' => Money::format($overview['gross_profit']),
                'net_profit_formatted' => Money::format($overview['net_profit']),
            ],
            'reports' => BakeryReports::catalogCards(),
        ]);
    }
}
