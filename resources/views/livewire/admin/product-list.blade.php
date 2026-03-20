<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">All Products</h5>
        <div class="d-flex gap-2">
            <input type="search" wire:model.debounce.300ms="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search products...">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">Add Product</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($products->isEmpty())
                <p class="p-4 text-muted mb-0">No products yet. <a href="{{ route('admin.products.create') }}">Add your first product</a></p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px">
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category ? $product->category->name : '—' }}</td>
                                    <td class="text-truncate" style="max-width:200px">{{ $product->description }}</td>
                                    <td>{{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($product->price, 2) }}</td>
                                    <td>{{ $product->stock_quantity }}{{ $product->variants_count ? ' +' . $product->variants_count . ' variants' : '' }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <button wire:click="deleteProduct({{ $product->id }})" onclick="return confirm('Delete this product?')" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
