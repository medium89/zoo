<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->boolean('personal_data_consent')->default(false)->after('status');
            $table->timestamp('personal_data_consent_at')->nullable()->after('personal_data_consent');
            $table->longText('personal_data_consent_text')->nullable()->after('personal_data_consent_at');
            $table->string('personal_data_consent_hash', 64)->nullable()->after('personal_data_consent_text');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn([
                'personal_data_consent',
                'personal_data_consent_at',
                'personal_data_consent_text',
                'personal_data_consent_hash',
            ]);
        });
    }
};

