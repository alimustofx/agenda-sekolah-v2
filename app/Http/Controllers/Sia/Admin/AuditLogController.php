<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\SiaTime;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AuditLog::query()->with('user:id,name')->latest('id');

        // Admin Humas: akses terbatas pada aktivitasnya sendiri.
        if ($user->role !== 'super_admin') {
            $query->where('user_id', $user->id);
        }
        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        return Inertia::render('Sia/Admin/AuditLog/Index', [
            'logs' => $query->paginate(25)->withQueryString()->through(fn ($l) => [
                'id' => $l->id, 'action' => $l->action, 'module' => $l->module, 'description' => $l->description,
                'user' => $l->user?->name ?? 'Sistem', 'ip' => $l->ip_address,
                'time' => $l->created_at->setTimezone(SiaTime::tz())->format('d/m/Y H.i.s'),
            ]),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'filters' => ['module' => $module],
            'limited' => $user->role !== 'super_admin',
        ]);
    }
}
