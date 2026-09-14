<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

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
        Route::get('/locations', [AdminController::class, 'locations']);
        Route::post('/locations', [AdminController::class, 'storeLocation']);
        Route::put('/locations/{id}', [AdminController::class, 'updateLocation']);
        Route::delete('/locations/{id}', [AdminController::class, 'destroyLocation']);
        
        Route::get('/lecturers', [AdminController::class, 'lecturers']);
        Route::post('/lecturers', [AdminController::class, 'storeLecturer']);
        Route::put('/lecturers/{id}', [AdminController::class, 'updateLecturer']);
        Route::delete('/lecturers/{id}', [AdminController::class, 'destroyLecturer']);
        
        // Rooms
        Route::get('/rooms', [AdminController::class, 'rooms']);
        Route::post('/rooms', [AdminController::class, 'storeRoom']);
        Route::put('/rooms/{id}', [AdminController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [AdminController::class, 'destroyRoom']);
        
        // Faculties
        Route::get('/faculties', [AdminController::class, 'faculties']);
        Route::post('/faculties', [AdminController::class, 'storeFaculty']);
        Route::put('/faculties/{id}', [AdminController::class, 'updateFaculty']);
        Route::delete('/faculties/{id}', [AdminController::class, 'destroyFaculty']);
        
        // Courses
        Route::get('/courses', [AdminController::class, 'courses']);
        Route::post('/courses', [AdminController::class, 'storeCourse']);
        Route::put('/courses/{id}', [AdminController::class, 'updateCourse']);
        Route::delete('/courses/{id}', [AdminController::class, 'destroyCourse']);

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
        Route::get('/academic-years', [AdminController::class, 'academicYears']);
        Route::get('/academic-years/active', [AdminController::class, 'activeAcademicYear']);
        Route::post('/academic-years', [AdminController::class, 'storeAcademicYear']);
        Route::put('/academic-years/{id}', [AdminController::class, 'updateAcademicYear']);
        Route::delete('/academic-years/{id}', [AdminController::class, 'destroyAcademicYear']);
        
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
