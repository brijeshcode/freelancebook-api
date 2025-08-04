<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $freelancerId = Auth::id();
        $period = $request->get('period', 30);
        $startDate = Carbon::now()->subDays($period);
        // Key Metrics
        $metrics = $this->getMetrics($freelancerId, $period);
        
        // Recent Invoices
        $recentInvoices = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->with(['client'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        // Top Clients
        $topClients = $this->getTopClients($freelancerId);
        
        // Upcoming Due Dates
        $upcomingDues = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->with(['client'])
            ->where('status', 'sent')
            ->where('due_date', '>=', Carbon::now())
            ->where('due_date', '<=', Carbon::now()->addDays(30))
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        // Chart Data
        $revenueChart = $this->getRevenueChartData($freelancerId, $period);
        $paymentStatusChart = $this->getPaymentStatusChartData($freelancerId);

        return ApiResponse::show('Dashboard data retrieved successfully', [
            'metrics' => $metrics,
            'recent_invoices' => $recentInvoices,
            'top_clients' => $topClients,
            'upcoming_dues' => $upcomingDues,
            'revenue_chart' => $revenueChart,
            'payment_status_chart' => $paymentStatusChart
        ]);
    }

    private function getMetrics($freelancerId, $period)
    {
        $startDate = Carbon::now()->subDays($period);
        $previousStartDate = Carbon::now()->subDays($period * 2);
        $previousEndDate = Carbon::now()->subDays($period);

        // Current period revenue
        $currentRevenue = Payment::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'completed')
            ->where('payment_date', '>=', $startDate)
            ->sum('amount_base_currency');

        // Previous period revenue for growth calculation
        $previousRevenue = Payment::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'completed')
            ->where('payment_date', '>=', $previousStartDate)
            ->where('payment_date', '<', $previousEndDate)
            ->sum('amount_base_currency');

        // Calculate growth percentage
        $revenueGrowth = $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        // Outstanding balance
        $totalInvoiced = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })->sum('total_amount_base_currency');

        $totalPaid = Payment::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'completed')
            ->sum('amount_base_currency');

        $outstandingBalance = $totalInvoiced - $totalPaid;

        // Overdue invoices
        $overdueInvoices = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('due_date', '<', Carbon::now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();

        // Active clients (clients with invoices in the period)
        $activeClients = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('created_at', '>=', $startDate)
            ->distinct('client_id')
            ->count('client_id');

        // Total clients
        $totalClients = Client::where('user_id', $freelancerId)->count();

        // This month revenue and invoices
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthRevenue = Payment::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'completed')
            ->where('payment_date', '>=', $thisMonthStart)
            ->sum('amount_base_currency');

        $thisMonthInvoices = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('created_at', '>=', $thisMonthStart)
            ->count();

        return [
            'total_revenue' => round($currentRevenue, 2),
            'revenue_growth' => $revenueGrowth,
            'outstanding_balance' => round($outstandingBalance, 2),
            'overdue_invoices' => $overdueInvoices,
            'active_clients' => $activeClients,
            'total_clients' => $totalClients,
            'this_month_revenue' => round($thisMonthRevenue, 2),
            'this_month_invoices' => $thisMonthInvoices
        ];
    }

    private function getTopClients($freelancerId)
    {
        return Client::where('user_id', $freelancerId)
            ->withSum(['invoices as total_revenue'], 'total_amount_base_currency')
            ->withCount('invoices as total_invoices')
            ->with(['invoices', 'payments'])
            ->get()
            ->map(function ($client) {
                $totalInvoiced = $client->invoices->sum('total_amount_base_currency');
                $totalPaid = $client->payments->where('status', 'completed')->sum('amount_base_currency');
                
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'total_revenue' => round($client->total_revenue ?? 0, 2),
                    'outstanding_balance' => round($totalInvoiced - $totalPaid, 2),
                    'total_invoices' => $client->total_invoices ?? 0
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5)
            ->values();
    }

    private function getRevenueChartData($freelancerId, $period)
    {
        $labels = [];
        $data = [];
        
        // Generate labels based on period
        if ($period <= 30) {
            // Daily data for last 30 days
            for ($i = $period - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->format('M d');
                
                $dayRevenue = Payment::whereHas('client', function($q) use ($freelancerId) {
                        $q->where('user_id', $freelancerId);
                    })
                    ->where('status', 'completed')
                    ->whereDate('payment_date', $date)
                    ->sum('amount_base_currency');
                
                $data[] = round($dayRevenue, 2);
            }
        } else {
            // Weekly data for longer periods
            $weeks = ceil($period / 7);
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
                $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
                $labels[] = $startOfWeek->format('M d');
                
                $weekRevenue = Payment::whereHas('client', function($q) use ($freelancerId) {
                        $q->where('user_id', $freelancerId);
                    })
                    ->where('status', 'completed')
                    ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
                    ->sum('amount_base_currency');
                
                $data[] = round($weekRevenue, 2);
            }
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getPaymentStatusChartData($freelancerId)
    {
        $paid = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'paid')
            ->sum('total_amount_base_currency');

        $pending = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'sent')
            ->where('due_date', '>=', Carbon::now())
            ->sum('total_amount_base_currency');

        $overdue = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('due_date', '<', Carbon::now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('total_amount_base_currency');

        $draft = Invoice::whereHas('client', function($q) use ($freelancerId) {
                $q->where('user_id', $freelancerId);
            })
            ->where('status', 'draft')
            ->sum('total_amount_base_currency');

        return [
            'labels' => ['Paid', 'Pending', 'Overdue', 'Draft'],
            'data' => [
                round($paid, 2),
                round($pending, 2),
                round($overdue, 2),
                round($draft, 2)
            ]
        ];
    }
}