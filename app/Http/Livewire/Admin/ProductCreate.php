<?php

namespace App\Http\Livewire\Admin;

use App\Category;
use App\Product;
use App\ProductVariant;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductCreate extends Component
{
    use WithFileUploads;

    public $name;
    public $description;
    public $price;
    public $image;
    public $stock_quantity = 0;
    public $category_id = '';
    public $variants = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'image' => 'nullable|image|max:2048',
        'stock_quantity' => 'required|integer|min:0',
        'category_id' => 'nullable|exists:categories,id',
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

        $product = Product::create([
            'category_id' => $this->category_id ?: null,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image' => $imagePath,
            'stock_quantity' => (int) $this->stock_quantity,
        ]);

        foreach ($this->variants as $v) {
            if (!empty($v['size']) || !empty($v['color'])) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $v['sku'] ?? null,
                    'size' => $v['size'] ?? null,
                    'color' => $v['color'] ?? null,
                    'price' => isset($v['price']) && $v['price'] !== '' ? $v['price'] : $this->price,
                    'stock_quantity' => (int) ($v['stock_quantity'] ?? 0),
                ]);
            }
        }

        session()->flash('message', 'Product created successfully.');
        return redirect()->route('admin.products.index');
    }

    public function addVariant()
    {
        $this->variants[] = ['size' => '', 'color' => '', 'price' => $this->price, 'stock_quantity' => 0, 'sku' => ''];
    }

    public function removeVariant($index)
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function render()
    {
        return view('livewire.admin.product-create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
