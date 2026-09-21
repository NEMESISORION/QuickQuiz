<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducatorQuizResultController;
use App\Http\Controllers\LearnerAttemptHistoryController;
use App\Http\Controllers\LearnerQuizController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionPositionController;
use App\Http\Controllers\QuizAttemptAnswerController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizAttemptResultController;
use App\Http\Controllers\QuizAttemptSubmissionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizPreviewController;
use App\Http\Controllers\QuizPublicationController;
use App\Http\Controllers\SecurityActivityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

        Route::middleware('role.selected')->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::middleware('can:access-educator-workspace')
                ->prefix('educator')
                ->name('educator.')
                ->group(function (): void {
                    Route::view('/', 'educator.dashboard')->name('dashboard');
                    Route::resource('quizzes', QuizController::class);
                    Route::get('quizzes/{quiz}/results', [EducatorQuizResultController::class, 'index'])
                        ->name('quizzes.results.index');
                    Route::scopeBindings()->group(function (): void {
                        Route::resource('quizzes.questions', QuestionController::class)
                            ->except(['index', 'show']);
                        Route::patch('quizzes/{quiz}/questions/{question}/position', QuestionPositionController::class)
                            ->name('quizzes.questions.position');
                    });
                    Route::get('quizzes/{quiz}/preview', QuizPreviewController::class)
                        ->name('quizzes.preview');
                    Route::post('quizzes/{quiz}/publication', [QuizPublicationController::class, 'store'])
                        ->name('quizzes.publication.store');
                });

            Route::middleware('can:access-learner-workspace')
                ->prefix('learner')
                ->name('learner.')
                ->group(function (): void {
                    Route::view('/', 'learner.dashboard')->name('dashboard');
                    Route::get('attempts', LearnerAttemptHistoryController::class)->name('attempts.index');
                    Route::get('quizzes', [LearnerQuizController::class, 'index'])->name('quizzes.index');
                    Route::get('quizzes/{quiz}', [LearnerQuizController::class, 'show'])->name('quizzes.show');
                    Route::post('quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store'])
                        ->name('quizzes.attempts.store');
                    Route::get('attempts/{quizAttempt}', [QuizAttemptController::class, 'show'])
                        ->name('attempts.show');
                    Route::post('attempts/{quizAttempt}/submission', [QuizAttemptSubmissionController::class, 'store'])
                        ->middleware('throttle:10,1')
                        ->name('attempts.submission.store');
                    Route::get('attempts/{quizAttempt}/result', QuizAttemptResultController::class)
                        ->name('attempts.result');
                    Route::put('attempts/{quizAttempt}/questions/{quizAttemptQuestion}/answer', QuizAttemptAnswerController::class)
                        ->middleware('throttle:120,1')
                        ->name('attempts.answers.update');
                });

            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('/profile/security', SecurityActivityController::class)
                ->name('profile.security');
        });
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
