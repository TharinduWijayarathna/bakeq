<div class="rounded-4xl bg-card p-8 shadow-sweet">
    <p class="font-script text-3xl text-primary">Join Bakeq</p>
    <h1 class="mt-1 text-3xl">Create your account</h1>

    <form wire:submit="register" class="mt-8 space-y-4">
        <div>
            <label for="name" class="mb-1 block text-sm font-semibold">Name</label>
            <input id="name" type="text" wire:model="name" autocomplete="name" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
            @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="phone" class="mb-1 block text-sm font-semibold">Phone</label>
            <input id="phone" type="text" wire:model="phone" autocomplete="tel" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
            @error('phone') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="address_line" class="mb-1 block text-sm font-semibold">Address</label>
            <input id="address_line" type="text" wire:model="address_line" autocomplete="street-address" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
            @error('address_line') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="city" class="mb-1 block text-sm font-semibold">City</label>
            <input id="city" type="text" wire:model="city" autocomplete="address-level2" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2">
            @error('city') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-semibold">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
            @error('password') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-semibold">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
        </div>
        <button type="submit" wire:loading.attr="disabled" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-bold text-primary-foreground shadow-soft disabled:opacity-70">
            <span wire:loading.remove>Create account</span>
            <span wire:loading.flex class="items-center gap-2">
                <x-spinner /> Creating account…
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-primary" wire:navigate>Sign in</a>
    </p>
</div>
