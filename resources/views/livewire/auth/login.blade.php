<div class="auth-card">
    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Login to your account</p>
    </div>

    <form wire:submit.prevent="login">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input 
                type="email" 
                id="email" 
                wire:model="email" 
                class="form-control @error('email') is-invalid @enderror"
                placeholder="Enter your email"
                autofocus
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
            <label class="checkbox-label">
                <input type="checkbox" wire:model="remember">
                <span>Remember me</span>
            </label>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">
                Login
            </button>
        </div>

        <div class="auth-footer">
            <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
        </div>
    </form>
</div>
