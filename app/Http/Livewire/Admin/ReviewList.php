<?php

namespace App\Http\Livewire\Admin;

use App\Review;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewList extends Component
{
    use WithPagination;

    public $approvedFilter = '';

    protected $queryString = ['approvedFilter' => ['except' => '']];

    public function updatingApprovedFilter()
    {
        $this->resetPage();
    }

    public function approve($id)
    {
        Review::findOrFail($id)->update(['is_approved' => true]);
        session()->flash('message', 'Review approved.');
    }

    public function deleteReview($id)
    {
        Review::findOrFail($id)->delete();
        session()->flash('message', 'Review deleted.');
    }

    public function render()
    {
        $query = Review::with(['user', 'product']);
        if ($this->approvedFilter === '1') {
            $query->where('is_approved', true);
        } elseif ($this->approvedFilter === '0') {
            $query->where('is_approved', false);
        }
        $reviews = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.admin.review-list', ['reviews' => $reviews]);
    }
}
