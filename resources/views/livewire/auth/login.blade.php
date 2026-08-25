<div class="rounded-4xl bg-card p-8 shadow-sweet">
    <p class="font-script text-3xl text-primary">Welcome back</p>
    <h1 class="mt-1 text-3xl">Sign in to {{ $brandShortName }}</h1>

    <form wire:submit="authenticate" class="mt-8 space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
            @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-semibold">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2" required>
            @error('password') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-input">
            Remember me
        </label>
        <button type="submit" wire:loading.attr="disabled" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-bold text-primary-foreground shadow-soft disabled:opacity-70">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading.flex class="items-center gap-2">
                <x-spinner /> Signing in…
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        New here?
        <a href="{{ route('register') }}" class="font-semibold text-primary" wire:navigate>Create an account</a>
    </p>
</div>
