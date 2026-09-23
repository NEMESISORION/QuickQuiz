<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducatorAnalyticsController;
use App\Http\Controllers\EducatorAnalyticsExportController;
use App\Http\Controllers\EducatorLiveSessionController;
use App\Http\Controllers\EducatorQuizResultController;
use App\Http\Controllers\LearnerAttemptHistoryController;
use App\Http\Controllers\LearnerLiveSessionController;
use App\Http\Controllers\LearnerQuizController;
use App\Http\Controllers\LiveSessionAnswerController;
use App\Http\Controllers\LiveSessionControlController;
use App\Http\Controllers\LiveSessionStateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationReadController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePasswordController;
use App\Http\Controllers\ProfilePreferenceController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionPositionController;
use App\Http\Controllers\QuizArchiveController;
use App\Http\Controllers\QuizAttemptAnswerController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizAttemptResultController;
use App\Http\Controllers\QuizAttemptSubmissionController;
use App\Http\Controllers\QuizCloseController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizDuplicateController;
use App\Http\Controllers\QuizPreviewController;
use App\Http\Controllers\QuizPublicationController;
use App\Http\Controllers\SecurityActivityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::get('/certificates/{verificationCode}', CertificateController::class)
    ->middleware('throttle:60,1')
    ->name('certificates.show');

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
                    Route::get('analytics', EducatorAnalyticsController::class)->name('analytics.index');
                    Route::get('analytics/export', EducatorAnalyticsExportController::class)
                        ->name('analytics.export');
                    Route::resource('quizzes', QuizController::class);
                    Route::post('quizzes/{quiz}/duplicate', QuizDuplicateController::class)
                        ->name('quizzes.duplicate');
                    Route::post('quizzes/{quiz}/archive', QuizArchiveController::class)
                        ->name('quizzes.archive');
                    Route::post('quizzes/{quiz}/close', QuizCloseController::class)
                        ->name('quizzes.close');
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
                    Route::post('quizzes/{quiz}/live-sessions', [EducatorLiveSessionController::class, 'store'])
                        ->middleware('throttle:10,1')
                        ->name('quizzes.live-sessions.store');
                    Route::get('live-sessions/{liveSession}', [EducatorLiveSessionController::class, 'show'])
                        ->name('live-sessions.show');
                    Route::post('live-sessions/{liveSession}/start', [LiveSessionControlController::class, 'start'])
                        ->name('live-sessions.start');
                    Route::post('live-sessions/{liveSession}/advance', [LiveSessionControlController::class, 'advance'])
                        ->name('live-sessions.advance');
                });

            Route::middleware('can:access-learner-workspace')
                ->prefix('learner')
                ->name('learner.')
                ->group(function (): void {
                    Route::view('/', 'learner.dashboard')->name('dashboard');
                    Route::get('attempts', LearnerAttemptHistoryController::class)->name('attempts.index');
                    Route::get('live', [LearnerLiveSessionController::class, 'create'])
                        ->name('live-sessions.create');
                    Route::post('live', [LearnerLiveSessionController::class, 'store'])
                        ->middleware('throttle:live-session-join')
                        ->name('live-sessions.store');
                    Route::get('live-sessions/{liveSession}', [LearnerLiveSessionController::class, 'show'])
                        ->name('live-sessions.show');
                    Route::post('live-sessions/{liveSession}/answer', LiveSessionAnswerController::class)
                        ->middleware('throttle:30,1')
                        ->name('live-sessions.answer');
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

            Route::match(['GET', 'POST'], '/live-sessions/{liveSession}/state', LiveSessionStateController::class)
                ->middleware('throttle:120,1')
                ->name('live-sessions.state');

            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::put('/profile/password', ProfilePasswordController::class)
                ->middleware('throttle:6,1')
                ->name('profile.password.update');
            Route::patch('/profile/preferences', [ProfilePreferenceController::class, 'update'])
                ->name('profile.preferences.update');
            Route::get('/profile/security', SecurityActivityController::class)
                ->name('profile.security');
            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications.index');
            Route::post('/notifications/read', [NotificationReadController::class, 'store'])
                ->name('notifications.read.store');
        });
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
