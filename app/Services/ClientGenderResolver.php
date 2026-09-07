<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class ClientGenderResolver
{
    public function __construct(private readonly AitunnelService $aitunnel)
    {
    }

    public function resolve(?string $fullName): string
    {
        $name = $this->firstName($fullName);
        if ($name === '') {
            return 'unknown';
        }

        $heuristic = $this->resolveByForm($name);
        if ($heuristic !== null || app()->environment('testing')) {
            return $heuristic ?? 'unknown';
        }

        return Cache::remember('client-gender:'.sha1(mb_strtolower($name)), now()->addDays(90), function () use ($name) {
            try {
                return $this->aitunnel->classifyPersonGender($name);
            } catch (Throwable) {
                return 'unknown';
            }
        });
    }

    private function firstName(?string $fullName): string
    {
        $clean = trim((string) preg_replace('/[^\p{L}\s-]+/u', ' ', (string) $fullName));

        return trim((string) preg_split('/\s+/u', $clean)[0]);
    }

    private function resolveByForm(string $name): ?string
    {
        $name = mb_strtolower($name);
        $maleA = ['илья', 'никита', 'лука', 'кузьма', 'данила', 'савва', 'фома'];
        $femaleSoft = ['любовь', 'нинэль', 'руфь', 'эсфирь', 'адель', 'николь', 'жасмин', 'мариам', 'нелли'];

        if (in_array($name, $maleA, true)) return 'male';
        if (in_array($name, $femaleSoft, true)) return 'female';
        if (preg_match('/(ович|евич|ич)$/u', $name)) return 'male';
        if (preg_match('/(овна|евна|ична)$/u', $name)) return 'female';
        if (preg_match('/[ая]$/u', $name)) return 'female';
        if (preg_match('/[бвгджзклмнпрстфхцчшщй]$/u', $name)) return 'male';

        return null;
    }
}
