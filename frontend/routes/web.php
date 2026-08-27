<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', [MainController::class, 'showLogin'])->name('login');
Route::post('/login', [MainController::class, 'login']);
Route::get('/register', [MainController::class, 'showRegister'])->name('register');
Route::post('/register', [MainController::class, 'register']);

Route::get('/dashboard', [MainController::class, 'dashboard'])->name('dashboard');
Route::get('/catalog', [MainController::class, 'catalog'])->name('catalog.index');

Route::middleware('fastapi.auth')->group(function () {
    Route::post('/logout', [MainController::class, 'logout'])->name('logout');
    Route::post('/user/switch-mode', [MainController::class, 'switchMode'])->name('user.switch-mode');

    Route::get('/booking', [MainController::class, 'booking'])->name('booking.index');
    Route::get('/wallet', [MainController::class, 'wallet'])->name('wallet.index');
    Route::post('/wallet/topup', [MainController::class, 'walletTopup'])->name('wallet.topup');
    Route::get('/settings', [MainController::class, 'settings'])->name('settings.index');
    Route::get('/notifications', [MainController::class, 'notifications'])->name('notifications.index');
    Route::get('/favorites', [MainController::class, 'favorites'])->name('favorites.index');
    Route::get('/reviews', [MainController::class, 'reviews'])->name('reviews.index');
    Route::post('/reviews', [MainController::class, 'submitReview'])->name('reviews.store');

    Route::post('/rides/start/{bike}', [MainController::class, 'bookRide'])->name('rides.start');
    Route::post('/booking/quote/{bike}', [MainController::class, 'quoteBooking'])->name('booking.quote');
    Route::post('/bikes/{bike}/favorite', [MainController::class, 'toggleFavorite'])->name('bikes.favorite');
    Route::post('/api/rides/{ride}/extend', [MainController::class, 'extendRide'])->name('rides.extend');
    Route::post('/api/rides/{ride}/end', [MainController::class, 'endRide'])->name('rides.end');
    Route::post('/offers/claim', [MainController::class, 'claimOffer'])->name('offers.claim');
    Route::post('/settings/profile', [MainController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [MainController::class, 'updatePassword'])->name('settings.password');

    Route::get('/owner/dashboard', [MainController::class, 'ownerDashboard'])->name('owner.dashboard');
    Route::get('/owner/bikes', [MainController::class, 'ownerBikes'])->name('owner.bikes');
    Route::get('/owner/bikes/create', [MainController::class, 'ownerBikeCreateForm'])->name('owner.bikes.create');
    Route::post('/owner/bikes', [MainController::class, 'ownerBikeCreate'])->name('owner.bikes.store');
    Route::post('/owner/bikes/{bike}/delete', [MainController::class, 'ownerDeleteBike'])->name('owner.bikes.delete');
    Route::get('/owner/bookings', [MainController::class, 'ownerBookings'])->name('owner.bookings');
    Route::post('/owner/bookings/{booking}/approve', [MainController::class, 'ownerApproveBooking'])->name('owner.bookings.approve');
    Route::post('/owner/bookings/{booking}/reject', [MainController::class, 'ownerRejectBooking'])->name('owner.bookings.reject');
    Route::get('/owner/earnings', [MainController::class, 'ownerEarnings'])->name('owner.earnings');
    Route::get('/owner/analytics', [MainController::class, 'ownerAnalytics'])->name('owner.analytics');

    // --- Agreements ---
    Route::post('/agreements/{booking}/generate', [MainController::class, 'generateAgreement'])->name('agreements.generate');
    Route::get('/agreements/{booking}/download', [MainController::class, 'downloadAgreement'])->name('agreements.download');
    Route::get('/agreements/{booking}/status', [MainController::class, 'agreementStatus'])->name('agreements.status');

    Route::get('/admin/dashboard', [MainController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/bikes', [MainController::class, 'adminBikes'])->name('admin.bikes');
    Route::get('/admin/bookings', [MainController::class, 'adminBookings'])->name('admin.bookings');

    // --- AI Features ---
    Route::post('/ai/chat', [MainController::class, 'aiChat'])->name('ai.chat');
    Route::post('/ai/recommend', [MainController::class, 'aiRecommend'])->name('ai.recommend');
    Route::post('/ai/price-prediction', [MainController::class, 'aiPricePrediction'])->name('ai.price-prediction');
    Route::post('/ai/demand-forecast', [MainController::class, 'aiDemandForecast'])->name('ai.demand-forecast');
    Route::post('/ai/fraud-detection', [MainController::class, 'aiFraudDetection'])->name('ai.fraud-detection');
    Route::post('/ai/maintenance-prediction', [MainController::class, 'aiMaintenancePrediction'])->name('ai.maintenance-prediction');
    Route::post('/ai/review-analysis', [MainController::class, 'aiReviewAnalysis'])->name('ai.review-analysis');
    Route::post('/ai/agreement-analysis', [MainController::class, 'aiAgreementAnalysis'])->name('ai.agreement-analysis');
    Route::post('/ai/semantic-search', [MainController::class, 'aiSemanticSearch'])->name('ai.semantic-search');
});
