<?php

namespace App\Http\Livewire\Admin;

use App\Category;
use App\Product;
use App\ProductVariant;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductEdit extends Component
{
    use WithFileUploads;

    public $product;
    public $name;
    public $description;
    public $price;
    public $image;
    public $stock_quantity;
    public $category_id = '';
    public $variants = [];

    public function mount($productId)
    {
        $this->product = Product::with('variants')->findOrFail($productId);
        $this->name = $this->product->name;
        $this->description = $this->product->description;
        $this->price = $this->product->price;
        $this->stock_quantity = $this->product->stock_quantity;
        $this->category_id = $this->product->category_id ? (string) $this->product->category_id : '';
        $this->variants = $this->product->variants->map(function ($v) {
            return ['id' => $v->id, 'size' => $v->size ?? '', 'color' => $v->color ?? '', 'price' => $v->price ?? '', 'stock_quantity' => $v->stock_quantity ?? 0, 'sku' => $v->sku ?? ''];
        })->toArray();
    }

    protected function getRules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }

    protected function getMessages()
    {
        return [
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
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, $this->getRules(), $this->getMessages());
    }

    public function update()
    {
        $this->validate($this->getRules(), $this->getMessages());

        $imagePath = $this->product->image;

        if ($this->image) {
            if ($this->product->image) {
                \Storage::disk('public')->delete($this->product->image);
            }
            $imagePath = $this->image->store('products', 'public');
        }

        $this->product->update([
            'category_id' => $this->category_id ?: null,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image' => $imagePath,
            'stock_quantity' => (int) $this->stock_quantity,
        ]);

        $existingIds = [];
        foreach ($this->variants as $v) {
            if (!empty($v['size']) || !empty($v['color'])) {
                $data = [
                    'size' => $v['size'] ?? null,
                    'color' => $v['color'] ?? null,
                    'price' => isset($v['price']) && $v['price'] !== '' ? $v['price'] : $this->price,
                    'stock_quantity' => (int) ($v['stock_quantity'] ?? 0),
                    'sku' => $v['sku'] ?? null,
                ];
                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $this->product->id)->find($v['id']);
                    if ($variant) {
                        $variant->update($data);
                        $existingIds[] = $variant->id;
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($data, ['product_id' => $this->product->id]));
                    $existingIds[] = $variant->id;
                }
            }
        }
        $this->product->variants()->whereNotIn('id', $existingIds)->delete();

        session()->flash('message', 'Product updated successfully.');
        return redirect()->route('admin.products.index');
    }

    public function addVariant()
    {
        $this->variants[] = ['id' => null, 'size' => '', 'color' => '', 'price' => $this->price, 'stock_quantity' => 0, 'sku' => ''];
    }

    public function removeVariant($index)
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function render()
    {
        return view('livewire.admin.product-edit', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
