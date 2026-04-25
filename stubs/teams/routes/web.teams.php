<?php

declare(strict_types=1);

use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

    Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('team-invitations.store');
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');
});

Route::get('/team-invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])
    ->middleware('signed')
    ->name('team-invitations.accept');
