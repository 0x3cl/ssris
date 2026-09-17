<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Enums\GovernmentCategory;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FeedbackLink;
use App\Models\FeedbackResponse;
use App\Models\ServiceRequest;
use App\Models\Visitor;
use App\Services\FeedbackScoreService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

class ReportController extends Controller
{
    private const TABS = ['demographics', 'clients', 'service-requests', 'feedback', 'site-visitors', 'audit-trails'];

    public function __construct(private readonly FeedbackScoreService $feedbackScores) {}

    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'demographics')->value();
        $tab = in_array($tab, self::TABS, true) ? $tab : 'demographics';

        [$start, $end, $periodLabel] = $this->resolvePeriod($request);

        $reportData = match ($tab) {
            'demographics' => $this->buildDemographics($request, $start, $end),
            'clients' => $this->buildClients($request, $start, $end),
            'service-requests' => $this->buildServiceRequests($request, $start, $end),
            'feedback' => $this->buildFeedback($request, $start, $end),
            'site-visitors' => $this->buildSiteVisitors($request, $start, $end),
            'audit-trails' => $this->buildAuditTrails($request, $start, $end),
        };

        return Inertia::render('admin/reports', [
            'tab' => $tab,
            'period' => [
                'type' => $this->periodType($request),
                'month' => $request->string('month')->value() ?: now()->format('Y-m'),
                'year' => (int) $request->integer('year', (int) now()->year),
                'quarter' => $this->quarter($request),
                'label' => $periodLabel,
            ],
            'reportData' => $reportData,
            'options' => [
                'clientTypes' => $this->enumOptions(ClientType::cases()),
                'businessRoles' => $this->enumOptions(ClientBusinessRole::cases()),
                'enterpriseSizes' => $this->enumOptions(ClientEnterpriseSize::cases()),
                'markets' => $this->enumOptions(ClientMarket::cases()),
                'sources' => $this->enumOptions(ClientSource::cases()),
                'services' => array_map(
                    fn (string $value): array => ['value' => $value, 'label' => ClientService::from($value)->label()],
                    $this->assignedServices(),
                ),
                'statuses' => $this->enumOptions(ServiceRequestStatus::cases()),
                'governmentCategories' => $this->enumOptions(GovernmentCategory::cases()),
            ],
        ]);
    }

    /** @return array<int, array{value: string, label: string}> */
    private function enumOptions(array $cases): array
    {
        return array_map(fn ($case): array => ['value' => $case->value, 'label' => $case->label()], $cases);
    }

    private function assignedServices(): array
    {
        return (Auth::user()?->services ?? collect())->pluck('service')->map(fn (ClientService $service): string => $service->value)->all();
    }

    /**
     * The "service" filter value to use: whatever was explicitly requested, or —
     * when the admin is scoped to exactly one service and none was requested —
     * that one service, so the filter isn't left on a moot "All services" state.
     */
    private function defaultServiceFilter(Request $request): string
    {
        $service = $request->string('service')->value();

        if ($service === '' && ! $request->has('service')) {
            $assignedServices = $this->assignedServices();

            if (count($assignedServices) === 1) {
                return $assignedServices[0];
            }
        }

        return $service;
    }

    private function clientType(mixed $value): ClientType
    {
        return $value instanceof ClientType ? $value : ClientType::from($value);
    }

    /** @return array<int, string> */
    private function governmentCategoryClientTypes(string $category): array
    {
        $category = GovernmentCategory::tryFrom($category);

        return $category === null ? [] : GovernmentCategory::clientTypeValuesFor($category);
    }

    private function periodType(Request $request): string
    {
        $type = $request->string('period', 'month')->value();

        return in_array($type, ['month', 'quarter', 'year'], true) ? $type : 'month';
    }

    private function quarter(Request $request): int
    {
        $quarter = (int) $request->integer('quarter', (int) ceil(now()->month / 3));

        return in_array($quarter, [1, 2, 3, 4], true) ? $quarter : 1;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function resolvePeriod(Request $request): array
    {
        return match ($this->periodType($request)) {
            'year' => $this->yearRange($request),
            'quarter' => $this->quarterRange($request),
            default => $this->monthRange($request),
        };
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function monthRange(Request $request): array
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $start = CarbonImmutable::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();

        return [$start, $start->endOfMonth(), $start->format('F Y')];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function quarterRange(Request $request): array
    {
        $year = (int) $request->integer('year', (int) now()->year);
        $quarter = $this->quarter($request);
        $start = CarbonImmutable::create($year, ($quarter - 1) * 3 + 1, 1)->startOfQuarter();

        return [$start, $start->endOfQuarter(), "Q{$quarter} {$year}"];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function yearRange(Request $request): array
    {
        $year = (int) $request->integer('year', (int) now()->year);
        $start = CarbonImmutable::create($year, 1, 1)->startOfYear();

        return [$start, $start->endOfYear(), (string) $year];
    }

    /** @return array<string, mixed> */
    private function buildDemographics(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $gender = $request->string('gender')->value();
        $ageBracket = $request->string('age_bracket')->value();
        $region = $request->string('region')->value();
        $typeClient = $request->string('type_client')->value();

        $base = Client::query()
            ->whereIn('service', $this->assignedServices())
            ->whereBetween('created_at', [$start, $end])
            ->when($gender !== '', fn ($query) => $query->where('gender', $gender))
            ->when($region !== '', fn ($query) => $query->where('region', $region))
            ->when($typeClient !== '', fn ($query) => $query->where('type_client', $typeClient))
            ->when($ageBracket !== '', function ($query) use ($ageBracket): void {
                $range = Client::ageBracketRange($ageBracket);

                if ($range !== null) {
                    $query->where('age', '>=', $range[0])->when($range[1] !== null, fn ($query) => $query->where('age', '<=', $range[1]));
                }
            });

        $genderBreakdown = (clone $base)->selectRaw('gender, COUNT(*) as total')->groupBy('gender')->pluck('total', 'gender');
        $regionBreakdown = (clone $base)->selectRaw('region, COUNT(*) as total')->groupBy('region')->orderByDesc('total')->get();
        $typeBreakdown = (clone $base)->selectRaw('type_client, COUNT(*) as total')->groupBy('type_client')->pluck('total', 'type_client');
        $ageBrackets = (clone $base)->pluck('age')->countBy(fn (?int $age): string => Client::ageBracket($age) ?? 'Unspecified');
        $governmentBreakdown = (clone $base)->pluck('type_client')->countBy(fn (mixed $type): string => GovernmentCategory::fromClientType($this->clientType($type))->label());

        return [
            'total' => (clone $base)->count(),
            'filters' => ['gender' => $gender, 'age_bracket' => $ageBracket, 'region' => $region, 'type_client' => $typeClient],
            'genderBreakdown' => $genderBreakdown->map(fn (int $total, string $gender): array => ['label' => $gender ?: 'Unspecified', 'total' => $total])->values(),
            'ageBracketBreakdown' => $ageBrackets->map(fn (int $total, string $bracket): array => ['label' => $bracket, 'total' => $total])->values(),
            'regionBreakdown' => $regionBreakdown->take(10)->map(fn ($row): array => ['label' => $row->region ?: 'Unspecified', 'total' => $row->total])->values(),
            'clientTypeBreakdown' => $typeBreakdown->map(fn (int $total, string $type): array => ['label' => $this->clientType($type)->label(), 'total' => $total])->values(),
            'governmentCategoryBreakdown' => $governmentBreakdown->map(fn (int $total, string $label): array => ['label' => $label, 'total' => $total])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildClients(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->value());
        $typeClient = $request->string('type_client')->value();
        $businessRole = $request->string('business_role')->value();
        $enterpriseSize = $request->string('enterprise_size')->value();
        $market = $request->string('market')->value();
        $source = $request->string('source')->value();
        $service = $this->defaultServiceFilter($request);
        $region = $request->string('region')->value();
        $status = $request->input('status') === 'archived' ? 'archived' : 'active';

        $base = Client::query()
            ->whereIn('service', $this->assignedServices())
            ->whereBetween('created_at', [$start, $end])
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when($typeClient !== '', fn ($query) => $query->where('type_client', $typeClient))
            ->when($businessRole !== '', fn ($query) => $query->where('business_role', $businessRole))
            ->when($enterpriseSize !== '', fn ($query) => $query->where('enterprise_size', $enterpriseSize))
            ->when($market !== '', fn ($query) => $query->where('market', $market))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->when($service !== '', fn ($query) => $query->where('service', $service))
            ->when($region !== '', fn ($query) => $query->where('region', $region))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    foreach (['fullname', 'email', 'mobile_no', 'company', 'school_name'] as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            });

        $clients = (clone $base)->orderByDesc('id')->paginate($entries)->withQueryString()
            ->through(fn (Client $client): array => [
                'id' => $client->id,
                'fullname' => $client->fullname,
                'email' => $client->email,
                'type' => $client->type_client?->label(),
                'service' => $client->service?->label(),
                'government_category' => GovernmentCategory::fromClientType($client->type_client)->label(),
                'region' => $client->region,
                'created_at' => $client->created_at->format('F j, Y'),
                'archived' => $client->trashed(),
            ]);

        return [
            'filters' => [
                'entries' => $entries,
                'search' => $search,
                'type_client' => $typeClient,
                'business_role' => $businessRole,
                'enterprise_size' => $enterpriseSize,
                'market' => $market,
                'source' => $source,
                'service' => $service,
                'region' => $region,
                'status' => $status,
            ],
            'clients' => $clients,
            'stats' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->withoutTrashed()->count(),
                'archived' => Client::query()->whereIn('service', $this->assignedServices())->whereBetween('created_at', [$start, $end])->onlyTrashed()->count(),
            ],
            'serviceBreakdown' => (clone $base)->selectRaw('service, COUNT(*) as total')->groupBy('service')->orderByDesc('total')->pluck('total', 'service')
                ->map(fn (int $total, string $value): array => ['label' => ClientService::from($value)->label(), 'total' => $total])->values(),
            'sourceBreakdown' => (clone $base)->selectRaw('source, COUNT(*) as total')->groupBy('source')->orderByDesc('total')->pluck('total', 'source')
                ->map(fn (int $total, string $value): array => ['label' => ClientSource::from($value)->label(), 'total' => $total])->values(),
            'marketBreakdown' => (clone $base)->whereNotNull('market')->selectRaw('market, COUNT(*) as total')->groupBy('market')->orderByDesc('total')->pluck('total', 'market')
                ->map(fn (int $total, string $value): array => ['label' => ClientMarket::from($value)->label(), 'total' => $total])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildServiceRequests(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->value());
        $status = $request->string('status')->value();
        $service = $this->defaultServiceFilter($request);
        $type = $request->string('type')->value();
        $region = $request->string('region')->value();
        $governmentCategory = $request->string('government_category')->value();

        $base = ServiceRequest::query()
            ->whereIn('service', $this->assignedServices())
            ->whereBetween('created_at', [$start, $end])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($service !== '', fn ($query) => $query->where('service', $service))
            ->when($type === 'walk-in', fn ($query) => $query->where('is_appointment', false))
            ->when($type === 'appointment', fn ($query) => $query->where('is_appointment', true))
            ->when($region !== '', fn ($query) => $query->whereHas('client', fn ($query) => $query->where('region', $region)))
            ->when($governmentCategory !== '', function ($query) use ($governmentCategory): void {
                $query->whereHas('client', fn ($query) => $query->whereIn('type_client', $this->governmentCategoryClientTypes($governmentCategory)));
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($query) => $query->where('fullname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            });

        $statusTotals = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $requests = (clone $base)->with('client:id,fullname,type_client,region')->latest()->paginate($entries)->withQueryString()
            ->through(fn (ServiceRequest $serviceRequest): array => [
                'id' => $serviceRequest->id,
                'service' => $serviceRequest->service->label(),
                'status' => $serviceRequest->status->label(),
                'type' => $serviceRequest->is_appointment ? 'Appointment' : 'Walk-in',
                'client' => $serviceRequest->client?->fullname,
                'government_category' => $serviceRequest->client?->type_client !== null
                    ? GovernmentCategory::fromClientType($serviceRequest->client->type_client)->label()
                    : null,
                'created_at' => $serviceRequest->created_at->format('F j, Y'),
            ]);

        return [
            'filters' => [
                'entries' => $entries,
                'search' => $search,
                'status' => $status,
                'service' => $service,
                'type' => $type,
                'region' => $region,
                'government_category' => $governmentCategory,
            ],
            'requests' => $requests,
            'stats' => [
                'total' => (clone $base)->count(),
                'walkIns' => (clone $base)->where('is_appointment', false)->count(),
                'appointments' => (clone $base)->where('is_appointment', true)->count(),
                'completed' => $statusTotals->get(ServiceRequestStatus::Completed->value, 0),
                'cancelled' => $statusTotals->get(ServiceRequestStatus::Cancelled->value, 0),
            ],
            'statusBreakdown' => collect(ServiceRequestStatus::cases())->map(fn (ServiceRequestStatus $status): array => [
                'label' => $status->label(),
                'total' => $statusTotals->get($status->value, 0),
            ]),
            'serviceBreakdown' => (clone $base)->selectRaw('service, COUNT(*) as total')->groupBy('service')->orderByDesc('total')->pluck('total', 'service')
                ->map(fn (int $total, string $value): array => ['label' => ClientService::from($value)->label(), 'total' => $total])->values(),
            'trend' => (clone $base)->selectRaw('DATE(created_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildFeedback(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $service = $this->defaultServiceFilter($request);
        $governmentCategory = $request->string('government_category')->value();
        $assignedServices = $this->assignedServices();

        $scopeServiceRequest = function ($query) use ($service, $governmentCategory, $assignedServices): void {
            $query->whereIn('service', $assignedServices)
                ->when($service !== '', fn ($query) => $query->where('service', $service))
                ->when($governmentCategory !== '', function ($query) use ($governmentCategory): void {
                    $query->whereHas('client', fn ($query) => $query->whereIn('type_client', $this->governmentCategoryClientTypes($governmentCategory)));
                });
        };

        $base = FeedbackResponse::query()
            ->whereBetween('feedback_responses.created_at', [$start, $end])
            ->whereHas('link.serviceRequest', $scopeServiceRequest);

        $summary = $this->feedbackScores->summarize((clone $base)->with(['link.serviceRequest.client']));

        $linksGenerated = FeedbackLink::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('serviceRequest', $scopeServiceRequest)
            ->count();

        $responses = (clone $base)->with(['link.serviceRequest.client'])->latest('feedback_responses.created_at')->paginate($entries)->withQueryString()
            ->through(function (FeedbackResponse $response): array {
                $serviceRequest = $response->link?->serviceRequest;

                return [
                    'id' => $response->id,
                    'service' => $serviceRequest?->service->label(),
                    'client' => $serviceRequest?->client?->fullname,
                    'government_category' => $serviceRequest?->client?->type_client !== null
                        ? GovernmentCategory::fromClientType($serviceRequest->client->type_client)->label()
                        : null,
                    'score' => $this->feedbackScores->scoreForResponse($response),
                    'submitted_at' => $response->created_at->format('F j, Y g:i A'),
                ];
            });

        return [
            'filters' => ['entries' => $entries, 'service' => $service, 'government_category' => $governmentCategory],
            'responses' => $responses,
            'stats' => [
                'totalResponses' => $summary['totalResponses'],
                'linksGenerated' => $linksGenerated,
                'responseRate' => $linksGenerated > 0 ? round(($summary['totalResponses'] / $linksGenerated) * 100, 1) : null,
                'overallScore' => $summary['overallScore'],
                'interpretation' => $summary['interpretation'],
            ],
            'perDimension' => $summary['perDimension'],
            'perCriterion' => $summary['perCriterion'],
            'ratingDistribution' => $summary['ratingDistribution'],
        ];
    }

    /** @return array<string, mixed> */
    private function buildSiteVisitors(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $entries = (int) $request->integer('entries', 10);
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

        $inPeriod = Visitor::query()->whereBetween('created_at', [$start, $end]);
        $activeInPeriod = Visitor::query()->whereBetween('updated_at', [$start, $end]);

        return [
            'filters' => compact('entries', 'search', 'date'),
            'visitors' => $visitors,
            'stats' => [
                'totalVisitors' => Visitor::query()->count(),
                'totalVisits' => (int) Visitor::query()->sum('total_visits'),
                'newInPeriod' => (clone $inPeriod)->count(),
                'activeInPeriod' => (clone $activeInPeriod)->count(),
            ],
            'newVisitorsSeries' => (clone $inPeriod)->selectRaw('DATE(created_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->get(),
            'activeVisitorsSeries' => (clone $activeInPeriod)->selectRaw('DATE(updated_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->get(),
            'providerBreakdown' => (clone $inPeriod)->selectRaw("COALESCE(NULLIF(provider, ''), 'Unknown') as provider, COUNT(*) as total")
                ->groupBy('provider')->orderByDesc('total')->limit(8)->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildAuditTrails(Request $request, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->value());
        $event = $request->string('event')->value();
        $date = $request->string('date')->value();

        $base = Audit::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($date !== '', fn ($query) => $query->whereDate('created_at', $date));

        $audits = (clone $base)->with('user')->orderByDesc('created_at')->orderByDesc('id')->paginate($entries)->withQueryString()
            ->through(fn (Audit $audit): array => [
                'id' => $audit->id,
                'event' => $audit->event,
                'user' => $audit->user->name ?? 'System',
                'model' => in_array($audit->event, ['login', 'logout'], true) ? 'Authentication' : Str::headline(class_basename((string) $audit->auditable_type)),
                'model_id' => $audit->auditable_id,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at?->format('F j, Y g:i A'),
            ]);

        return [
            'filters' => compact('entries', 'search', 'event', 'date'),
            'audits' => $audits,
            'events' => ['created', 'updated', 'deleted', 'restored', 'login', 'logout'],
            'stats' => [
                'total' => (clone $base)->count(),
            ],
            'eventBreakdown' => (clone $base)->selectRaw('event, COUNT(*) as total')->groupBy('event')->orderByDesc('total')->get()
                ->map(fn ($row): array => ['label' => Str::headline($row->event), 'total' => $row->total]),
            'trend' => (clone $base)->selectRaw('DATE(created_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->get(),
        ];
    }
}
