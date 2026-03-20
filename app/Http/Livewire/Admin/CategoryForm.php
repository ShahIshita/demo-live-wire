<?php

namespace App\Http\Livewire\Admin;

use App\Category;
use Illuminate\Support\Str;
use Livewire\Component;

class CategoryForm extends Component
{
    public $categoryId = null;
    public $parent_id = '';
    public $name = '';
    public $slug = '';
    public $description = '';
    public $sort_order = 0;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|min:2|max:100',
        'slug' => 'required|alpha_dash|max:100',
        'parent_id' => 'nullable|exists:categories,id',
        'sort_order' => 'integer|min:0',
    ];

    public function mount($categoryId = null)
    {
        $this->categoryId = $categoryId;
        if ($categoryId) {
            $cat = Category::findOrFail($categoryId);
            $this->parent_id = $cat->parent_id ? (string) $cat->parent_id : '';
            $this->name = $cat->name;
            $this->slug = $cat->slug;
            $this->description = $cat->description ?? '';
            $this->sort_order = $cat->sort_order;
            $this->is_active = $cat->is_active;
        }
    }

    public function updatedName($value)
    {
        if (!$this->categoryId) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate();
        $data = [
            'parent_id' => $this->parent_id ?: null,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'sort_order' => (int) $this->sort_order,
            'is_active' => $this->is_active,
        ];
        if ($this->categoryId) {
            $cat = Category::findOrFail($this->categoryId);
            $cat->update($data);
            session()->flash('message', 'Category updated.');
        } else {
            Category::create($data);
            session()->flash('message', 'Category created.');
        }
        return redirect()->route('admin.categories.index');
    }

    public function render()
    {
        $parents = Category::when($this->categoryId, function ($q) {
            $q->where('id', '!=', $this->categoryId);
        })->orderBy('name')->get();
        return view('livewire.admin.category-form', ['parents' => $parents]);
    }
}
