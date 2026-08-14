<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterAdminDepartmentsRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\Admin\AdminDepartmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDepartmentController extends Controller
{
    public function index(
        FilterAdminDepartmentsRequest $request,
        AdminDepartmentService $departments,
    ): View {
        $filters = $request->validated();
        $paginated = $departments->paginate($filters);
        $paginated->getCollection()->each(function (Department $department) use ($departments): void {
            $department->setAttribute(
                'active_storage',
                $departments->formatBytes((int) ($department->active_storage_bytes ?? 0)),
            );
            $department->setAttribute(
                'trashed_storage',
                $departments->formatBytes((int) ($department->trashed_storage_bytes ?? 0)),
            );
        });

        return view('admin.departments', [
            ...$this->layoutData($request),
            'departments' => $paginated,
            'filters' => $filters,
        ]);
    }

    public function show(
        Request $request,
        Department $department,
        AdminDepartmentService $departments,
    ): View {
        return view('admin.department-show', [
            ...$this->layoutData($request),
            ...$departments->detail($department),
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
