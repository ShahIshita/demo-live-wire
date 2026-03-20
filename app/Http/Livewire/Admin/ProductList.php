<?php

namespace App\Http\Livewire\Admin;

use App\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

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
        $query = Product::with('category')->withCount('variants');
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.admin.product-list', ['products' => $products]);
    }
}
