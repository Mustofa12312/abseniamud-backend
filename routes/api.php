<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\MasterDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::get('/summary', [AttendanceController::class, 'summary']);
        Route::post('/corrections', [AttendanceController::class, 'storeCorrection']);
        Route::get('/corrections', [AttendanceController::class, 'getCorrections']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('role:super_admin,admin_akademik')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/attendance', [AdminController::class, 'attendance']);
        Route::get('/attendance/{id}', [AdminController::class, 'attendanceDetails']);
        // Master Data
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{id}', [LocationController::class, 'update']);
        Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
        
        Route::get('/lecturers', [LecturerController::class, 'index']);
        Route::post('/lecturers', [LecturerController::class, 'store']);
        Route::put('/lecturers/{id}', [LecturerController::class, 'update']);
        Route::delete('/lecturers/{id}', [LecturerController::class, 'destroy']);
        
        // Rooms
        Route::get('/rooms', [MasterDataController::class, 'rooms']);
        Route::post('/rooms', [MasterDataController::class, 'storeRoom']);
        Route::put('/rooms/{id}', [MasterDataController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [MasterDataController::class, 'destroyRoom']);
        
        // Faculties
        Route::get('/faculties', [MasterDataController::class, 'faculties']);
        Route::post('/faculties', [MasterDataController::class, 'storeFaculty']);
        Route::put('/faculties/{id}', [MasterDataController::class, 'updateFaculty']);
        Route::delete('/faculties/{id}', [MasterDataController::class, 'destroyFaculty']);
        
        // Courses
        Route::get('/courses', [MasterDataController::class, 'courses']);
        Route::post('/courses', [MasterDataController::class, 'storeCourse']);
        Route::put('/courses/{id}', [MasterDataController::class, 'updateCourse']);
        Route::delete('/courses/{id}', [MasterDataController::class, 'destroyCourse']);

        // Positions & Assignments
        Route::get('/positions', [\App\Http\Controllers\AssignmentController::class, 'positions']);
        Route::post('/positions', [\App\Http\Controllers\AssignmentController::class, 'storePosition']);
        Route::put('/positions/{id}', [\App\Http\Controllers\AssignmentController::class, 'updatePosition']);
        Route::delete('/positions/{id}', [\App\Http\Controllers\AssignmentController::class, 'destroyPosition']);

        Route::get('/assignments', [\App\Http\Controllers\AssignmentController::class, 'assignments']);
        Route::post('/assignments', [\App\Http\Controllers\AssignmentController::class, 'storeAssignment']);
        Route::put('/assignments/{id}', [\App\Http\Controllers\AssignmentController::class, 'updateAssignment']);
        Route::delete('/assignments/{id}', [\App\Http\Controllers\AssignmentController::class, 'destroyAssignment']);

        // Academic Years
        Route::get('/academic-years', [MasterDataController::class, 'academicYears']);
        Route::get('/academic-years/active', [MasterDataController::class, 'activeAcademicYear']);
        Route::post('/academic-years', [MasterDataController::class, 'storeAcademicYear']);
        Route::put('/academic-years/{id}', [MasterDataController::class, 'updateAcademicYear']);
        Route::delete('/academic-years/{id}', [MasterDataController::class, 'destroyAcademicYear']);
        
        // Users Management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        Route::get('/reports', [AdminController::class, 'reports']);
        
        // Schedules
        Route::get('/schedules', [\App\Http\Controllers\ScheduleController::class, 'index']);
        Route::post('/schedules', [\App\Http\Controllers\ScheduleController::class, 'store']);
        Route::put('/schedules/{id}', [\App\Http\Controllers\ScheduleController::class, 'update']);
        Route::delete('/schedules/{id}', [\App\Http\Controllers\ScheduleController::class, 'destroy']);
        
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);
        
        // Admin Corrections
        Route::get('/corrections', [AdminController::class, 'corrections']);
        Route::post('/corrections/{id}/approve', [AdminController::class, 'approveCorrection']);
        Route::post('/corrections/{id}/reject', [AdminController::class, 'rejectCorrection']);
        
        // Audit Logs
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    });
});
