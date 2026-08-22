<div class="mx-auto max-w-3xl px-5 py-12">
    <p class="font-script text-3xl text-primary">Account</p>
    <h1 class="mt-1 text-4xl">Your profile</h1>
    <x-flash />

    <form wire:submit="saveProfile" class="mt-8 space-y-4 rounded-4xl bg-card p-6 shadow-soft sm:p-8">
        <h2 class="text-2xl">Details</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
                @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Email</label>
                <input type="email" wire:model="email" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
                @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Phone</label>
                <input type="text" wire:model="phone" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
                @error('phone') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">City</label>
                <input type="text" wire:model="city" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
                @error('city') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Address</label>
                <input type="text" wire:model="address_line" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
                @error('address_line') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        </div>
        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save profile</button>
    </form>

    <form wire:submit="updatePassword" class="mt-6 space-y-4 rounded-4xl bg-card p-6 shadow-soft sm:p-8">
        <h2 class="text-2xl">Change password</h2>
        <div>
            <label class="mb-1 block text-sm font-semibold">Current password</label>
            <input type="password" wire:model="current_password" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
            @error('current_password') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">New password</label>
            <input type="password" wire:model="password" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
            @error('password') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Confirm new password</label>
            <input type="password" wire:model="password_confirmation" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
        </div>
        <button type="submit" class="rounded-full bg-secondary px-6 py-3 text-sm font-bold text-secondary-foreground">Update password</button>
    </form>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('orders.index') }}" class="rounded-full bg-muted px-5 py-2 text-sm font-semibold" wire:navigate>My orders</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-full px-5 py-2 text-sm font-semibold text-muted-foreground">Sign out</button>
        </form>
    </div>
</div>
