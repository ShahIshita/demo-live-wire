<div class="auth-card">
    <div class="auth-header">
        <h2>Create Account</h2>
        <p>Sign up to get started</p>
    </div>

    <form wire:submit.prevent="register">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input 
                type="text" 
                id="name" 
                wire:model="name" 
                class="form-control @error('name') is-invalid @enderror"
                placeholder="Enter your full name"
                autofocus
            >
            @error('name') 
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input 
                type="email" 
                id="email" 
                wire:model="email" 
                class="form-control @error('email') is-invalid @enderror"
                placeholder="Enter your email"
            >
            @error('email') 
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                wire:model="password" 
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Enter your password"
            >
            @error('password') 
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input 
                type="password" 
                id="password_confirmation" 
                wire:model="password_confirmation" 
                class="form-control"
                placeholder="Confirm your password"
            >
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">
                Register
            </button>
        </div>

        <div class="auth-footer">
            <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
        </div>
    </form>
</div>
