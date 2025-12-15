<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_links', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('href');
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        DB::table('nav_links')->insert([
            [
                'key' => 'index',
                'label' => 'Главная',
                'href' => '#index',
                'active' => true,
                'order' => 1,
            ],
            [
                'key' => 'about',
                'label' => 'Обо мне',
                'href' => '#about',
                'active' => true,
                'order' => 2,
            ],
            [
                'key' => 'advantages',
                'label' => 'Преимущества',
                'href' => '#advantages',
                'active' => true,
                'order' => 3,
            ],
            [
                'key' => 'services',
                'label' => 'Услуги',
                'href' => '#services',
                'active' => true,
                'order' => 4,
            ],
            [
                'key' => 'gallery',
                'label' => 'Фотоальбом',
                'href' => '#gallery',
                'active' => true,
                'order' => 5,
            ],
            [
                'key' => 'contacts',
                'label' => 'Контакты',
                'href' => '#contacts',
                'active' => true,
                'order' => 6,
            ],
            [
                'key' => 'articles',
                'label' => 'Статьи',
                'href' => '/articles',
                'active' => true,
                'order' => 7,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_links');
    }
};

