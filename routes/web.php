// 管理者用メール設定ルート
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
Route::get('/email-settings', [App\Http\Controllers\Admin\EmailSettingController::class, 'index'])->name('email-settings.index');
Route::put('/email-settings', [App\Http\Controllers\Admin\EmailSettingController::class, 'update'])->name('email-settings.update');
Route::post('/email-settings/test', [App\Http\Controllers\Admin\EmailSettingController::class, 'sendTest'])->name('email-settings.test');
});