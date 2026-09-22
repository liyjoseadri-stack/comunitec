<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validatedData($request));

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);

        if ($data['password'] === null) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $user->update($request->validate(['active' => ['required', 'boolean']]));

        return redirect()->route('admin.users.index')->with('status', 'Estado actualizado.');
    }

    /** @return array{name:string,email:string,role:string,password:string|null} */
    private function validatedData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_COMMERCIAL, User::ROLE_CONSULTATION])],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', 'min:8'],
        ]);
    }
}
