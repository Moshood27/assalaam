<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->string('name');
            $blueprint->text('description')->nullable();
            $blueprint->date('date');
            $blueprint->time('start_time');
            $blueprint->time('end_time');
            $blueprint->decimal('venue_lat', 10, 8)->nullable();
            $blueprint->decimal('venue_lng', 11, 8)->nullable();
            $blueprint->integer('radius_meters')->default(50);
            $blueprint->string('pin');
            $blueprint->decimal('fine_amount', 12, 2)->default(500);
            $blueprint->decimal('apology_fee_amount', 12, 2)->default(500);
            $blueprint->enum('status', ['scheduled', 'ongoing', 'completed', 'audited'])->default('scheduled');
            $blueprint->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $blueprint->enum('status', ['present', 'absent', 'apology_paid', 'fine_paid'])->default('absent');
            $blueprint->timestamp('attended_at')->nullable();
            $blueprint->timestamp('apology_paid_at')->nullable();
            $blueprint->decimal('lat', 10, 8)->nullable();
            $blueprint->decimal('lng', 11, 8)->nullable();
            $blueprint->timestamp('fine_paid_at')->nullable();
            $blueprint->timestamps();

            $blueprint->unique(['user_id', 'meeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('meetings');
    }
};
