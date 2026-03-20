<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Product Reviews</h5>
        <select wire:model="approvedFilter" class="form-select form-select-sm" style="width: 150px;">
            <option value="">All</option>
            <option value="1">Approved</option>
            <option value="0">Pending</option>
        </select>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($reviews->isEmpty())
                <p class="p-4 text-muted mb-0">No reviews yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviews as $r)
                                <tr>
                                    <td>{{ $r->product->name ?? '—' }}</td>
                                    <td>{{ $r->user->name ?? '—' }}</td>
                                    <td>{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</td>
                                    <td class="text-truncate" style="max-width: 200px">{{ $r->comment }}</td>
                                    <td>
                                        @if ($r->is_approved)
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if (!$r->is_approved)
                                            <button type="button" wire:click="approve({{ $r->id }})" class="btn btn-sm btn-outline-success">Approve</button>
                                        @endif
                                        <button type="button" wire:click="deleteReview({{ $r->id }})" onclick="return confirm('Delete this review?')" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">{{ $reviews->links() }}</div>
            @endif
        </div>
    </div>
</div>
