<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['image', 'sticker_image', 'title', 'text', 'tags', 'sticker_doodle', 'sticker_color', 'tape_color', 'active', 'order'];
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
        'tags' => 'array',
    ];

    private const DEFAULT_STICKER_DOODLES = [
        'Передержка' => 'paw.svg',
        'Выгул собак' => 'paw-trail.svg',
        'Уход за кошками' => 'paw.svg',
        'Уход за грызунами' => 'carrot.svg',
        'Уход за попугаями' => 'heart.svg',
        'Уход за растениями' => 'leaf.svg',
        'Уход за рыбками' => 'bubbles.svg',
        'Приём лекарств' => 'cross.svg',
    ];

    public function stickerImageUrl(): string
    {
        return $this->sticker_image
            ? asset('storage/' . $this->sticker_image)
            : asset('images/image-placeholder.svg');
    }

    public function stickerDoodleUrl(): string
    {
        if ($this->sticker_doodle) {
            return asset('storage/' . $this->sticker_doodle);
        }

        return asset('images/v2/services/' . (self::DEFAULT_STICKER_DOODLES[$this->title] ?? 'heart.svg'));
    }
}
