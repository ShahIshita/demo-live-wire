<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">All Users</h5>
        <div class="d-flex gap-2">
            <input type="search" wire:model.debounce.300ms="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search users...">
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($users->isEmpty())
                <p class="p-4 text-muted mb-0">No users found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @forelse ($user->roles as $role)
                                            <span class="badge bg-secondary">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($user->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if ($editingUserId === $user->id)
                                            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                                                <div class="form-check">
                                                    <input type="checkbox" wire:model="editIsActive" class="form-check-input" id="active-{{ $user->id }}">
                                                    <label class="form-check-label" for="active-{{ $user->id }}">Active</label>
                                                </div>
                                                <div>
                                                    <label class="form-label small">Roles</label>
                                                    @foreach ($roles as $role)
                                                        <div class="form-check">
                                                            <input type="checkbox" wire:model="editRoleIds" value="{{ $role->id }}" class="form-check-input" id="role-{{ $user->id }}-{{ $role->id }}">
                                                            <label class="form-check-label" for="role-{{ $user->id }}-{{ $role->id }}">{{ $role->name }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" wire:click="saveUser" class="btn btn-sm btn-primary">Save</button>
                                                    <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
                                                </div>
                                            </div>
                                        @else
                                            <button type="button" wire:click="editUser({{ $user->id }})" class="btn btn-sm btn-outline-primary">Edit</button>
                                            <button type="button" wire:click="toggleActive({{ $user->id }})" class="btn btn-sm btn-outline-{{ $user->is_active ? 'danger' : 'success' }}">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
