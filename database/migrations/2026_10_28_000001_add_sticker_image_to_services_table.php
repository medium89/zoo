<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('sticker_image')->nullable()->after('image');
        });

        // Copy the current V2 artwork into managed storage without touching V1 images.
        $illustrations = [
            'Передержка' => 'boarding.webp',
            'Выгул собак' => 'walking.webp',
            'Уход за кошками' => 'cats.webp',
            'Уход за грызунами' => 'rodents.webp',
            'Уход за попугаями' => 'parrots.webp',
            'Уход за растениями' => 'plants.webp',
            'Уход за рыбками' => 'fish.webp',
            'Приём лекарств' => 'medicine.webp',
        ];

        foreach ($illustrations as $title => $filename) {
            if (!DB::table('services')->where('title', $title)->whereNull('sticker_image')->exists()) {
                continue;
            }

            $source = public_path('images/v2/services/'.$filename);
            if (!is_file($source)) {
                throw new RuntimeException('Не найдена иллюстрация услуги V2: '.$source);
            }

            $target = 'services/stickers/'.$filename;
            if (!Storage::disk('public')->exists($target)
                && !Storage::disk('public')->put($target, file_get_contents($source))) {
                throw new RuntimeException('Не удалось сохранить иллюстрацию услуги V2: '.$target);
            }

            DB::table('services')->where('title', $title)->whereNull('sticker_image')
                ->update(['sticker_image' => $target]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sticker_image');
        });
    }
};
