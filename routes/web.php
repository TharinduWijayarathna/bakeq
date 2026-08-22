<?php

use App\Livewire\Admin\CakeForm;
use App\Livewire\Admin\CakeIndex as AdminCakeIndex;
use App\Livewire\Admin\CategoryIndex as AdminCategoryIndex;
use App\Livewire\Admin\CustomerIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DesignerManager;
use App\Livewire\Admin\OrderIndex as AdminOrderIndex;
use App\Livewire\Admin\OrderShow as AdminOrderShow;
use App\Livewire\Admin\TestimonialIndex as AdminTestimonialIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CakeAssistant;
use App\Livewire\CakeCatalog;
use App\Livewire\CakeDesigner;
use App\Livewire\CakeShow;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Livewire\HomePage;
use App\Livewire\OrderIndex;
use App\Livewire\ProfilePage;
use App\Livewire\WishlistPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', HomePage::class)->name('home');
Route::livewire('/cakes', CakeCatalog::class)->name('cakes.index');
Route::livewire('/cakes/{cake:slug}', CakeShow::class)->name('cakes.show');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::livewire('/designer', CakeDesigner::class)->name('designer');
    Route::livewire('/assistant', CakeAssistant::class)->name('assistant');
    Route::livewire('/cart', CartPage::class)->name('cart');
    Route::livewire('/wishlist', WishlistPage::class)->name('wishlist');
    Route::livewire('/checkout', CheckoutPage::class)->name('checkout');
    Route::livewire('/orders', OrderIndex::class)->name('orders.index');
    Route::livewire('/profile', ProfilePage::class)->name('profile');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/categories', AdminCategoryIndex::class)->name('categories');
    Route::livewire('/cakes', AdminCakeIndex::class)->name('cakes.index');
    Route::livewire('/cakes/create', CakeForm::class)->name('cakes.create');
    Route::livewire('/cakes/{cake}/edit', CakeForm::class)->name('cakes.edit');
    Route::livewire('/orders', AdminOrderIndex::class)->name('orders.index');
    Route::livewire('/orders/{order}', AdminOrderShow::class)->name('orders.show');
    Route::livewire('/designer', DesignerManager::class)->name('designer');
    Route::livewire('/testimonials', AdminTestimonialIndex::class)->name('testimonials');
    Route::livewire('/customers', CustomerIndex::class)->name('customers');
});
