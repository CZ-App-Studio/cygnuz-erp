<?php

use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\UserAccountStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HRCore\Enums\AttendanceType;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('users', function (Blueprint $table) {

      $table->id();
      $table->string('tenant_id', 191)->nullable();
      $table->string('first_name', 50)->nullable();
      $table->string('last_name', 50)->nullable();
      $table->string('user_name')->nullable()->unique();
      $table->string('name', 100)->nullable();

      $table->text('profile_picture')->nullable();
      $table->timestamp('email_verified_at')->nullable();
      $table->timestamp('phone_verified_at')->nullable();
      $table->timestamp('last_login')->nullable();
      $table->string('password');

      //Personal Info
      $table->string('code', 50)->unique();
      $table->string('email', 100)->unique();
      $table->string('phone', 20)->unique()->nullable();
      $table->string('address', 1000)->nullable();
      $table->string('alternate_number')->nullable();
      $table->date('dob')->nullable();
      $table->string('gender')->default(Gender::MALE->value);

      //Employment Info
      $table->date('date_of_joining')->nullable();
      //Attendance Info
      $table->string('attendance_type',191)->default(AttendanceType::OPEN->value);

      $table->string('status')->default(UserAccountStatus::ACTIVE->value);

      $table->string('language',191)->default(Language::ENGLISH->value);

      //Foreign Keys
      $table->foreignId('reporting_to_id')->nullable()->constrained('users')->onDelete('set null');

      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->softDeletes();
      $table->rememberToken();
      $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table) {
      $table->string('email')->primary();
      $table->string('token');
      $table->timestamp('created_at')->nullable();
    });

    Schema::create('sessions', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->foreignId('user_id')->nullable()->index();
      $table->string('ip_address', 45)->nullable();
      $table->text('user_agent')->nullable();
      $table->longText('payload');
      $table->integer('last_activity')->index();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('users');
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('sessions');
  }
};
