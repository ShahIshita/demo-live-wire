<?php

namespace App\Http\Livewire\Admin;

use App\Product;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductCreate extends Component
{
    use WithFileUploads;

    public $name;
    public $description;
    public $price;
    public $image;
    public $stock_quantity;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'image' => 'nullable|image|max:2048',
        'stock_quantity' => 'required|integer|min:0',
    ];

    protected $messages = [
        'name.required' => 'The product name is required.',
        'price.required' => 'The price is required.',
        'price.numeric' => 'The price must be a number.',
        'price.min' => 'The price must be at least 0.',
        'image.image' => 'The file must be an image (jpeg, png, bmp, gif, svg, or webp).',
        'image.max' => 'The image must not exceed 2MB.',
        'stock_quantity.required' => 'The stock quantity is required.',
        'stock_quantity.integer' => 'The stock quantity must be a whole number.',
        'stock_quantity.min' => 'The stock quantity must be at least 0.',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, $this->rules, $this->messages);
    }

    public function save()
    {
        $this->validate($this->rules, $this->messages);

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('products', 'public');
        }

        Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image' => $imagePath,
            'stock_quantity' => (int) $this->stock_quantity,
        ]);

        $this->reset(['name', 'description', 'price', 'image', 'stock_quantity']);
        session()->flash('message', 'Product created successfully.');
    }

    public function render()
    {
        return view('livewire.admin.product-create');
    }
}
