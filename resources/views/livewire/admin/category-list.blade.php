<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Categories</h5>
        <div class="d-flex gap-2">
            <input type="search" wire:model.debounce.300ms="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search...">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($categories->isEmpty())
                <p class="p-4 text-muted mb-0">No categories. <a href="{{ route('admin.categories.create') }}">Create one</a></p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $cat)
                                <tr>
                                    <td>{{ $cat->name }}</td>
                                    <td>{{ $cat->parent ? $cat->parent->name : '—' }}</td>
                                    <td>{{ $cat->products_count }}</td>
                                    <td>
                                        @if ($cat->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.categories.edit', $cat->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <button type="button" wire:click="deleteCategory({{ $cat->id }})" onclick="return confirm('Delete this category?')" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>
</div>
