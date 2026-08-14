<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterAdminUsersRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(
        FilterAdminUsersRequest $request,
        AdminUserService $users,
    ): View {
        $filters = $request->validated();

        return view('admin.users', [
            ...$this->layoutData($request),
            'users' => $users->paginate($filters),
            'summary' => $users->summary(),
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('display_name')->orderBy('name')->get(['name', 'display_name']),
        ]);
    }

    public function show(
        Request $request,
        User $user,
        AdminUserService $users,
    ): View {
        return view('admin.user-show', [
            ...$this->layoutData($request),
            ...$users->detail($user),
        ]);
    }

    /**
     * @return array{user: array<string, string>}
     */
    private function layoutData(Request $request): array
    {
        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();
        $authenticatedUser->loadMissing('department:id,name');

        return [
            'user' => [
                'name' => trim("{$authenticatedUser->name} {$authenticatedUser->last_name}"),
                'department' => $authenticatedUser->department?->name ?? 'Sin departamento',
                'avatar' => $authenticatedUser->avatarUrl(),
            ],
        ];
    }
}
