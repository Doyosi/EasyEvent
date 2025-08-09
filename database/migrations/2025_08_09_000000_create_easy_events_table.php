<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = config('easy-event.table', 'easy_events');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable()->index(); // external id if needed
            $table->string('type')->index();                 // e.g., webinar, meeting
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('status')->default('draft')->index(); // draft|published|archived
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('easy-event.table', 'easy_events'));
    }
};
