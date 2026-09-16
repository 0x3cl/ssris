<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteVisitorController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $selectedMonth = CarbonImmutable::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();

        $entries = $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->value());
        $date = $request->string('date')->value();

        $visitors = Visitor::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%")
                        ->orWhere('user_agent', 'like', "%{$search}%");
                });
            })
            ->when($date !== '', fn ($query) => $query->whereDate('created_at', $date))
            ->orderByDesc('updated_at')
            ->paginate($entries)
            ->withQueryString()
            ->through(fn (Visitor $visitor): array => [
                'id' => $visitor->id,
                'ip_address' => $visitor->ip_address,
                'user_agent' => $visitor->user_agent,
                'provider' => $visitor->provider,
                'total_visits' => $visitor->total_visits,
                'first_seen' => $visitor->created_at->format('F j, Y g:i A'),
                'last_seen' => $visitor->updated_at->format('F j, Y g:i A'),
            ]);

        $monthlyVisitors = Visitor::query()->whereBetween('created_at', [$selectedMonth, $selectedMonth->endOfMonth()]);
        $activeInMonth = Visitor::query()->whereBetween('updated_at', [$selectedMonth, $selectedMonth->endOfMonth()]);

        $month = $selectedMonth->format('Y-m');

        return Inertia::render('admin/site-visitors', [
            'filters' => compact('entries', 'search', 'date', 'month'),
            'visitors' => $visitors,
            'stats' => [
                'totalVisitors' => Visitor::query()->count(),
                'totalVisits' => (int) Visitor::query()->sum('total_visits'),
                'newThisMonth' => (clone $monthlyVisitors)->count(),
                'activeThisMonth' => (clone $activeInMonth)->count(),
            ],
            'newVisitorsSeries' => (clone $monthlyVisitors)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'activeVisitorsSeries' => (clone $activeInMonth)
                ->selectRaw('DATE(updated_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'providerBreakdown' => (clone $monthlyVisitors)
                ->selectRaw("COALESCE(NULLIF(provider, ''), 'Unknown') as provider, COUNT(*) as total")
                ->groupBy('provider')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
        ]);
    }
}
