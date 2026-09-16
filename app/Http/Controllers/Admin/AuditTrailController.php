<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

class AuditTrailController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->value());
        $event = $request->string('event')->value();
        $date = $request->string('date')->value();

        $audits = Audit::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($date !== '', fn ($query) => $query->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($entries)
            ->withQueryString()
            ->through(fn (Audit $audit): array => [
                'id' => $audit->id,
                'event' => $audit->event,
                'user' => $audit->user->name ?? 'System',
                'model' => in_array($audit->event, ['login', 'logout'], true)
                    ? 'Authentication'
                    : Str::headline(class_basename((string) $audit->auditable_type)),
                'model_id' => $audit->auditable_id,
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at?->format('F j, Y g:i A'),
            ]);

        return Inertia::render('admin/audit-trails', [
            'filters' => compact('entries', 'search', 'event', 'date'),
            'audits' => $audits,
            'events' => ['created', 'updated', 'deleted', 'restored', 'login', 'logout'],
        ]);
    }
}
