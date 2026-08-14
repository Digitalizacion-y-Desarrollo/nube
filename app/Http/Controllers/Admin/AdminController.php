<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(
        Request $request,
        AdminDashboardService $dashboard,
    ): View {
        return view('admin.dashboard', [
            ...$this->layoutData($request),
            ...$dashboard->data(),
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
