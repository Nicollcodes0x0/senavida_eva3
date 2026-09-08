<?php

use App\Http\Controllers\Api\V1\Auth\PatientAccessController;
use App\Http\Controllers\Api\V1\AdminStatsController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatMessageController;
use App\Http\Controllers\Api\V1\ConsentController;
use App\Http\Controllers\Api\V1\HealthCenterController;
use App\Http\Controllers\Api\V1\MedicalSessionController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PictogramCategoryController;
use App\Http\Controllers\Api\V1\PictogramController;
use App\Http\Controllers\Api\V1\SecuritySettingController;
use App\Http\Controllers\Api\V1\TemporaryAccessCodeController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::post('/patients/{patient}/attention-codes', [TemporaryAccessCodeController::class, 'store']);

    Route::post('/auth/patient/redeem', [PatientAccessController::class, 'redeem'])
        ->middleware('throttle:5,1');

    Route::post('/auth/patient/logout', [PatientAccessController::class, 'logout'])
        ->middleware(['auth:sanctum', 'patient.only']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/pictogram-categories', [PictogramCategoryController::class, 'index']);
        Route::get('/pictograms', [PictogramController::class, 'index']);

        Route::get('/medical-sessions/{medicalSession}/messages', [ChatMessageController::class, 'index']);
        Route::post('/medical-sessions/{medicalSession}/messages', [ChatMessageController::class, 'store'])
            ->middleware('session.active');
        Route::post('/messages/{message}/confirm', [ChatMessageController::class, 'confirm']);
        Route::post('/messages/{message}/read', [ChatMessageController::class, 'markAsRead']);

        Route::get('/medical-sessions/{medicalSession}/consents', [ConsentController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'patient.only'])->group(function () {
        Route::post('/consent-requests/{consent}/approve', [ConsentController::class, 'approve']);
        Route::post('/consent-requests/{consent}/reject', [ConsentController::class, 'reject']);
        Route::post('/consent-requests/{consent}/revoke', [ConsentController::class, 'revoke']);
    });

    Route::middleware(['auth:sanctum', 'staff.only'])->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'register']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/restore', [UserController::class, 'restore']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
        Route::patch('/organizations/{organization}/restore', [OrganizationController::class, 'restore']);

        Route::get('/health-centers', [HealthCenterController::class, 'index']);
        Route::post('/health-centers', [HealthCenterController::class, 'store']);
        Route::get('/health-centers/{healthCenter}', [HealthCenterController::class, 'show']);
        Route::put('/health-centers/{healthCenter}', [HealthCenterController::class, 'update']);
        Route::delete('/health-centers/{healthCenter}', [HealthCenterController::class, 'destroy']);
        Route::patch('/health-centers/{healthCenter}/restore', [HealthCenterController::class, 'restore']);

        Route::get('/units', [UnitController::class, 'index']);
        Route::post('/units', [UnitController::class, 'store']);
        Route::get('/units/{unit}', [UnitController::class, 'show']);
        Route::put('/units/{unit}', [UnitController::class, 'update']);
        Route::delete('/units/{unit}', [UnitController::class, 'destroy']);
        Route::patch('/units/{unit}/restore', [UnitController::class, 'restore']);

        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);
        Route::post('/attention-codes/validate', [TemporaryAccessCodeController::class, 'validateCode']);

        Route::post('/medical-sessions/{medicalSession}/consent-requests', [ConsentController::class, 'store'])
            ->middleware('session.active');

        Route::post('/pictograms', [PictogramController::class, 'store']);
        Route::patch('/pictograms/{pictogram}', [PictogramController::class, 'update']);
        Route::delete('/pictograms/{pictogram}', [PictogramController::class, 'destroy']);
        Route::patch('/pictograms/{pictogram}/restore', [PictogramController::class, 'restore']);

        Route::get('/admin/stats', [AdminStatsController::class, 'index']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::post('/audit-logs/export', [AuditLogController::class, 'export']);

        Route::get('/security-settings', [SecuritySettingController::class, 'show']);
        Route::put('/security-settings', [SecuritySettingController::class, 'update']);

        Route::post('/pictogram-categories', [PictogramCategoryController::class, 'store']);
        Route::patch('/pictogram-categories/{pictogramCategory}', [PictogramCategoryController::class, 'update']);
        Route::delete('/pictogram-categories/{pictogramCategory}', [PictogramCategoryController::class, 'destroy']);
        Route::patch('/pictogram-categories/{pictogramCategory}/restore', [PictogramCategoryController::class, 'restore']);

        Route::post('/medical-sessions', [MedicalSessionController::class, 'store']);
        Route::get('/medical-sessions/active', [MedicalSessionController::class, 'active']);
        Route::get('/medical-sessions/{medicalSession}', [MedicalSessionController::class, 'show']);
        Route::patch('/medical-sessions/{medicalSession}/stage', [MedicalSessionController::class, 'advance'])
            ->middleware('session.active');
        Route::post('/medical-sessions/{medicalSession}/close', [MedicalSessionController::class, 'close'])
            ->middleware('session.active');
    });
});