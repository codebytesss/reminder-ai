<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReminderController;
use Illuminate\Http\Request;
use App\Models\Reminder;

Route::get('/', [ReminderController::class, 'index']);

Route::get('/api/reminder', function () {
    $reminders = Reminder::all();

    return response()->json([
        'message' => 'success',
        'data' => $reminders
    ]);
});

Route::post('/api/reminder', [ReminderController::class, 'createReminder'])->name('reminder.create');
Route::delete('/api/reminder/{id}', [ReminderController::class, 'deleteReminder'])->name('reminder.delete');

// Route::get('/', function () {
//     return view('reminder');
// });
