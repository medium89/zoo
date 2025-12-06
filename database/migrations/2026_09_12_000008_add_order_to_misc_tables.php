<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('description');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('seo_charset');
        });

        Schema::table('article_comments', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
        });

        $this->seedOrders('animals');
        $this->seedOrders('feedback');
        $this->seedOrders('articles');
        $this->seedOrders('article_comments');
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('article_comments', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }

    private function seedOrders(string $table): void
    {
        $rows = DB::table($table)->orderBy('id')->get(['id']);
        $order = 1;
        foreach ($rows as $row) {
            DB::table($table)->where('id', $row->id)->update(['order' => $order++]);
        }
    }
};
