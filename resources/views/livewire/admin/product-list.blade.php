<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="product-header">
        <h2>All Products</h2>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
    </div>

    <div class="product-table-container">
        @if ($products->isEmpty())
            <p class="empty-state">No products yet. <a href="{{ route('admin.products.create') }}">Add your first product</a></p>
        @else
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
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
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-thumb">
                                @else
                                    <span class="no-image">No image</span>
                                @endif
                            </td>
                            <td>{{ $product->name }}</td>
                            <td class="desc-cell">{{ Str::limit($product->description, 50) }}</td>
                            <td>${{ number_format($product->price, 2) }}</td>
                            <td>{{ $product->stock_quantity }}</td>
                            <td>
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-edit">Edit</a>
                                <button wire:click="deleteProduct({{ $product->id }})" 
                                        onclick="return confirm('Are you sure you want to delete this product?')"
                                        class="btn btn-sm btn-delete">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
