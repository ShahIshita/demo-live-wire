<?php

namespace App\Http\Livewire\Admin;

use App\Category;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryList extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteCategory($id)
    {
        $cat = Category::findOrFail($id);
        if ($cat->products()->exists()) {
            session()->flash('error', 'Cannot delete category with products. Reassign products first.');
            return;
        }
        if ($cat->children()->exists()) {
            session()->flash('error', 'Cannot delete category with subcategories. Delete or move children first.');
            return;
        }
        $cat->delete();
        session()->flash('message', 'Category deleted.');
    }

    public function render()
    {
        $query = Category::with('parent')->withCount('products');
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        $categories = $query->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('livewire.admin.category-list', ['categories' => $categories]);
    }
}
