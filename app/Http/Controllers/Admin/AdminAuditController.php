<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterAdminAuditRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    public function index(FilterAdminAuditRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.audit', [
            ...$this->layoutData($request),
            'logs' => $this->audit->paginate($filters),
            'summary' => $this->audit->summary(),
            'filters' => $filters,
            'actions' => $this->audit->actions(),
            'resourceTypes' => $this->audit->resourceTypes(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'people' => User::query()
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
        ]);
    }

    public function show(Request $request, AuditLog $log): View
    {
        return view('admin.audit-show', [
            ...$this->layoutData($request),
            ...$this->audit->detail($log),
        ]);
    }

    /**
     * @return array{user: array<string, string>}
     */
    private function layoutData(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('department:id,name');

        return [
            'user' => [
                'name' => trim("{$user->name} {$user->last_name}"),
                'department' => $user->department?->name ?? 'Sin departamento',
                'avatar' => $user->avatarUrl(),
            ],
        ];
    }
}
