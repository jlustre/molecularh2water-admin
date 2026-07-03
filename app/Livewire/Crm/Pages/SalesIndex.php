<?php

namespace App\Livewire\Crm\Pages;

use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\QuotationStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Order;
use App\Models\Crm\Quotation;
use App\Services\Crm\ExecutiveAnalyticsService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class SalesIndex extends Component
{
    use UsesCrmLayout;

    public string $period = '30d';

    /** @var array<string, int|float> */
    public array $summary = [];

    public function mount(ExecutiveAnalyticsService $analytics): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.view'), 403);

        $this->loadSummary($analytics);
    }

    public function updatedPeriod(ExecutiveAnalyticsService $analytics): void
    {
        $this->loadSummary($analytics);
    }

    public function contactUrl(?Model $contact): ?string
    {
        if (! $contact) {
            return null;
        }

        return match ($contact->getMorphClass()) {
            'lead' => CrmRoutes::url('leads.show', ['lead' => $contact]),
            'prospect' => CrmRoutes::url('prospects.show', ['lead' => $contact]),
            'customer' => CrmRoutes::url('customers.show', ['lead' => $contact]),
            'recruit' => CrmRoutes::url('recruits.show', ['lead' => $contact]),
            default => null,
        };
    }

    public function contactLabel(?Model $contact): string
    {
        if (! $contact) {
            return 'Unknown contact';
        }

        if (method_exists($contact, 'fullName')) {
            return $contact->fullName() ?: 'Unnamed contact';
        }

        return 'Unknown contact';
    }

    public function render(): View
    {
        $user = auth()->user();

        $recentOrders = Schema::hasTable('orders')
            ? Order::query()
                ->forAccessibleContacts($user)
                ->with(['contact', 'author'])
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $recentQuotations = Schema::hasTable('quotations')
            ? Quotation::query()
                ->forAccessibleContacts($user)
                ->with(['contact', 'author', 'order'])
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        return view('livewire.crm.pages.sales-index', [
            'recentOrders' => $recentOrders,
            'recentQuotations' => $recentQuotations,
        ])->layout($this->crmLayout());
    }

    private function loadSummary(ExecutiveAnalyticsService $analytics): void
    {
        $user = auth()->user();
        $start = $this->periodStart($this->period);

        $ordersQuery = Schema::hasTable('orders')
            ? Order::query()->forAccessibleContacts($user)
            : null;

        $quotationsQuery = Schema::hasTable('quotations')
            ? Quotation::query()->forAccessibleContacts($user)
            : null;

        $ordersCount = $ordersQuery
            ? (clone $ordersQuery)->when($start, fn ($q) => $q->where('created_at', '>=', $start))->count()
            : 0;

        $quotationsCount = $quotationsQuery
            ? (clone $quotationsQuery)->when($start, fn ($q) => $q->where('created_at', '>=', $start))->count()
            : 0;

        $openQuotations = $quotationsQuery
            ? (clone $quotationsQuery)
                ->whereIn('status', [
                    QuotationStatus::Draft->value,
                    QuotationStatus::Presented->value,
                    QuotationStatus::Viewed->value,
                ])
                ->count()
            : 0;

        $pendingPayments = $ordersQuery
            ? (clone $ordersQuery)
                ->whereIn('payment_status', [
                    PaymentStatus::Pending->value,
                    PaymentStatus::Partial->value,
                ])
                ->whereNotIn('status', [OrderStatus::Cancelled->value])
                ->count()
            : 0;

        $this->summary = [
            'orders' => $ordersCount,
            'quotations' => $quotationsCount,
            'open_quotations' => $openQuotations,
            'pending_payments' => $pendingPayments,
            'revenue' => $analytics->totalRevenue($this->period, $user),
        ];
    }

    private function periodStart(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null,
        };
    }
}
