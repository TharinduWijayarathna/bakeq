<?php

use App\Http\Controllers\Admin\InvoiceDownloadController;
use App\Http\Controllers\Admin\ReportDownloadController;
use App\Http\Controllers\CheckoutPaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Admin\AdminAgentChat;
use App\Livewire\Admin\AuditLogIndex;
use App\Livewire\Admin\CakeForm;
use App\Livewire\Admin\CakeIndex as AdminCakeIndex;
use App\Livewire\Admin\CategoryIndex as AdminCategoryIndex;
use App\Livewire\Admin\CustomerIndex;
use App\Livewire\Admin\CustomerShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DesignerManager;
use App\Livewire\Admin\EmployeeIndex;
use App\Livewire\Admin\GalleryManager;
use App\Livewire\Admin\InventoryIndex;
use App\Livewire\Admin\InvoiceIndex;
use App\Livewire\Admin\OrderIndex as AdminOrderIndex;
use App\Livewire\Admin\OrderShow as AdminOrderShow;
use App\Livewire\Admin\PosTerminal;
use App\Livewire\Admin\ProductionBoard;
use App\Livewire\Admin\RecipeForm;
use App\Livewire\Admin\RecipeIndex;
use App\Livewire\Admin\ReportShow;
use App\Livewire\Admin\ReportsIndex;
use App\Livewire\Admin\ShiftIndex;
use App\Livewire\Admin\TestimonialIndex as AdminTestimonialIndex;
use App\Livewire\Admin\WasteIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CakeAssistant;
use App\Livewire\CakeCatalog;
use App\Livewire\CakeDesigner;
use App\Livewire\CakeShow;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Livewire\GalleryPage;
use App\Livewire\HomePage;
use App\Livewire\OrderIndex;
use App\Livewire\ProfilePage;
use App\Livewire\WishlistPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', HomePage::class)->name('home');
Route::livewire('/cakes', CakeCatalog::class)->name('cakes.index');
Route::livewire('/cakes/{cake:slug}', CakeShow::class)->name('cakes.show');
Route::livewire('/gallery', GalleryPage::class)->name('gallery');

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

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::middleware('auth')->group(function (): void {
    Route::livewire('/designer', CakeDesigner::class)->name('designer');
    Route::livewire('/assistant', CakeAssistant::class)->name('assistant');
    Route::livewire('/cart', CartPage::class)->name('cart');
    Route::livewire('/wishlist', WishlistPage::class)->name('wishlist');
    Route::livewire('/checkout', CheckoutPage::class)->name('checkout');
    Route::get('/checkout/payment/{order}/success', [CheckoutPaymentController::class, 'success'])->name('checkout.payment.success');
    Route::get('/checkout/payment/{order}/cancel', [CheckoutPaymentController::class, 'cancel'])->name('checkout.payment.cancel');
    Route::livewire('/orders', OrderIndex::class)->name('orders.index');
    Route::livewire('/profile', ProfilePage::class)->name('profile');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('staff.can:dashboard')->group(function (): void {
        Route::livewire('/', Dashboard::class)->name('dashboard');
    });
    Route::middleware('staff.can:reports')->group(function (): void {
        Route::livewire('/reports', ReportsIndex::class)->name('reports.index');
        Route::get('/reports/{report}/pdf', ReportDownloadController::class)->name('reports.download');
        Route::livewire('/reports/{report}', ReportShow::class)->name('reports.show');
    });
    Route::middleware('staff.can:categories')->group(function (): void {
        Route::livewire('/categories', AdminCategoryIndex::class)->name('categories');
    });
    Route::middleware('staff.can:cakes')->group(function (): void {
        Route::livewire('/cakes', AdminCakeIndex::class)->name('cakes.index');
        Route::livewire('/cakes/create', CakeForm::class)->name('cakes.create');
        Route::livewire('/cakes/{cake}/edit', CakeForm::class)->name('cakes.edit');
    });
    Route::middleware('staff.can:inventory')->group(function (): void {
        Route::livewire('/inventory', InventoryIndex::class)->name('inventory');
    });
    Route::middleware('staff.can:recipes')->group(function (): void {
        Route::livewire('/recipes', RecipeIndex::class)->name('recipes.index');
        Route::livewire('/recipes/create', RecipeForm::class)->name('recipes.create');
        Route::livewire('/recipes/{recipe}/edit', RecipeForm::class)->name('recipes.edit');
    });
    Route::middleware('staff.can:orders')->group(function (): void {
        Route::livewire('/orders', AdminOrderIndex::class)->name('orders.index');
        Route::livewire('/orders/{order}', AdminOrderShow::class)->name('orders.show');
    });
    Route::middleware('staff.can:pos')->group(function (): void {
        Route::livewire('/pos', PosTerminal::class)->name('pos');
    });
    Route::middleware('staff.can:order-assistant')->group(function (): void {
        Route::redirect('/order-assistant', '/admin/orders?tab=ai')->name('order-assistant');
    });
    Route::middleware('staff.can:admin-agent')->group(function (): void {
        Route::livewire('/agent', AdminAgentChat::class)->name('admin-agent');
    });
    Route::middleware('staff.can:production')->group(function (): void {
        Route::livewire('/production', ProductionBoard::class)->name('production');
    });
    Route::middleware('staff.can:waste')->group(function (): void {
        Route::livewire('/waste', WasteIndex::class)->name('waste');
    });
    Route::middleware('staff.can:invoices')->group(function (): void {
        Route::livewire('/invoices', InvoiceIndex::class)->name('invoices.index');
        Route::get('/invoices/{invoice}/download', InvoiceDownloadController::class)->name('invoices.download');
    });
    Route::middleware('staff.can:designer')->group(function (): void {
        Route::livewire('/designer', DesignerManager::class)->name('designer');
    });
    Route::middleware('staff.can:testimonials')->group(function (): void {
        Route::livewire('/testimonials', AdminTestimonialIndex::class)->name('testimonials');
    });
    Route::middleware('staff.can:customers')->group(function (): void {
        Route::livewire('/customers', CustomerIndex::class)->name('customers');
        Route::livewire('/customers/{customer}', CustomerShow::class)->name('customers.show');
    });
    Route::middleware('staff.can:employees')->group(function (): void {
        Route::livewire('/employees', EmployeeIndex::class)->name('employees');
    });
    Route::middleware('staff.can:shifts')->group(function (): void {
        Route::livewire('/shifts', ShiftIndex::class)->name('shifts');
    });
    Route::middleware('staff.can:audit')->group(function (): void {
        Route::livewire('/audit', AuditLogIndex::class)->name('audit');
    });
    Route::middleware('staff.can:gallery')->group(function (): void {
        Route::livewire('/gallery', GalleryManager::class)->name('gallery');
    });
});
