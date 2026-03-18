<div style="text-align: center; padding: 20px;">
    <h1>Livewire Counter Example</h1>
    <h2 style="font-size: 48px; margin: 20px 0;">{{ $count }}</h2>
    <div>
        <button wire:click="increment" style="padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer;">
            Increment +
        </button>
        <button wire:click="decrement" style="padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer;">
            Decrement -
        </button>
    </div>
</div>
