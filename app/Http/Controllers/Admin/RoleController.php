<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        // $roles = Role::all();
        $roles = Role::query()->whereNot('name', 'admin')->latest()->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
        ]);

        Role::query()->create($validated);

        return redirect()->route('admin.roles.index')
            ->with('message', 'Role Added!');
    }

    public function edit(Role $role)
    {
        return view('admin.role.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('message', 'Role is updated!');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('message', 'The Role deleted');
    }
}
