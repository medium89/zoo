<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // New installations already create this column as nullable in the
        // original migration. Existing installations have applied the old
        // alteration, so no schema change is required here.
    }

    public function down(): void
    {
        // Kept intentionally empty: making a nullable column required again
        // would be destructive for existing content.
    }
};
