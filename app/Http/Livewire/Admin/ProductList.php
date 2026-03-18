<?php

namespace App\Http\Livewire\Admin;

use App\Product;
use Livewire\Component;

class ProductList extends Component
{
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            \Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        session()->flash('message', 'Product deleted successfully.');
    }

    public function render()
    {
        return view('livewire.admin.product-list', [
            'products' => Product::orderBy('created_at', 'desc')->get(),
        ]);
    }
}
