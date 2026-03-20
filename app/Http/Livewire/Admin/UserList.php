<?php

namespace App\Http\Livewire\Admin;

use App\Role;
use App\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public $search = '';
    public $editingUserId = null;
    public $editRoleIds = [];
    public $editIsActive = true;

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editUser($userId)
    {
        $user = User::with('roles')->findOrFail($userId);
        $this->editingUserId = $userId;
        $this->editRoleIds = $user->roles->pluck('id')->toArray();
        $this->editIsActive = (bool) $user->is_active;
    }

    public function cancelEdit()
    {
        $this->editingUserId = null;
        $this->editRoleIds = [];
    }

    public function saveUser()
    {
        $user = User::findOrFail($this->editingUserId);
        $user->is_active = $this->editIsActive;
        $user->save();
        $user->roles()->sync($this->editRoleIds);
        session()->flash('message', 'User updated successfully.');
        $this->cancelEdit();
    }

    public function toggleActive($userId)
    {
        $user = User::findOrFail($userId);
        $user->is_active = !$user->is_active;
        $user->save();
        session()->flash('message', 'User ' . ($user->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function render()
    {
        $query = User::query()->with('roles');
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::orderBy('name')->get();

        return view('livewire.admin.user-list', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
