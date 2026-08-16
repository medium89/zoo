<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AitunnelService
{
    public function extractIntent(string $text): array
    {
        $apiKey = config('services.aitunnel.api_key');
        if (!$apiKey) {
            throw new RuntimeException('AITUNNEL_API_KEY is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->post($this->baseUrl().'/chat/completions', [
                'model' => config('services.aitunnel.chat_model', 'gemini-2.5-flash-lite'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('AITunnel request failed: '.$response->status());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (!is_array($decoded)) {
            throw new RuntimeException('AITunnel returned invalid JSON.');
        }

        return $decoded;
    }

    public function transcribeAudio(string $bytes, string $filename = 'voice.ogg'): string
    {
        $apiKey = config('services.aitunnel.api_key');
        if (!$apiKey) {
            throw new RuntimeException('AITUNNEL_API_KEY is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->attach('file', $bytes, $filename)
            ->post($this->baseUrl().'/audio/transcriptions', [
                'model' => config('services.aitunnel.stt_model', 'whisper-1'),
                'language' => 'ru',
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('AITunnel transcription failed: '.$response->status());
        }

        return trim((string)$response->json('text'));
    }

    /**
     * Определяет, является ли короткая характеристика питомца/клиента
     * положительной или требующей внимания.
     */
    public function classifyTag(string $tag): array
    {
        $apiKey = config('services.aitunnel.api_key');
        if (!$apiKey) {
            throw new RuntimeException('AITUNNEL_API_KEY is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl().'/chat/completions', [
                'model' => config('services.aitunnel.chat_model', 'gemini-2.5-flash-lite'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<'PROMPT'
Ты классифицируешь один короткий тег для карточки питомца или клиента зоосервиса.
Верни только JSON без Markdown по схеме:
{"type":"positive|negative","reason":"краткое объяснение по-русски"}

positive — приятное или полезное качество: дружелюбный, спокойный, привит, постоянный клиент.
negative — то, что требует внимания, осторожности или влияет на уход: кусается, боится людей, аллергия, агрессивный.
Если тег нейтральный, двусмысленный или неизвестный, выбирай negative, чтобы администратор его заметил.
reason — до 90 символов, без оценок личности и без лишних слов.
PROMPT,
                    ],
                    ['role' => 'user', 'content' => trim($tag)],
                ],
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('AITunnel tag classification failed: '.$response->status());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (!is_array($decoded)) {
            throw new RuntimeException('AITunnel returned invalid tag classification JSON.');
        }

        $type = ($decoded['type'] ?? null) === 'positive' ? 'positive' : 'negative';
        $reason = trim((string) ($decoded['reason'] ?? ''));

        return [
            'type' => $type,
            'reason' => mb_substr($reason, 0, 90),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.aitunnel.base_url', 'https://api.aitunnel.ru/v1'), '/');
    }

    private function systemPrompt(): string
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        return <<<PROMPT
Ты извлекаешь намерения администратора зоосервиса из русских сообщений для Telegram-бота.
Сегодня: {$today}. Часовой пояс: Asia/Barnaul.

Верни только JSON без Markdown.

Схема:
{
  "intent": "create_booking|list_bookings|delete_booking|show_pet|show_client|attach_pet_photo|answer_yes|answer_no|cancel|unknown",
  "service_type": "передержка|выгул|уход|null",
  "units_per_day": "целое число 1-24|null",
  "start_date": "YYYY-MM-DD|null",
  "end_date": "YYYY-MM-DD|null",
  "period_label": "строка|null",
  "delete_scope": "single|upcoming|null",
  "animal": {
    "name": "строка|null",
    "species": "кот|кошка|собака|пес|пёс|щенок|другое|null",
    "size": "мелкая|средняя|крупная|null",
    "description": "строка|null"
  },
  "client": {
    "name": "строка|null",
    "phone": "строка|null",
    "note": "строка|null"
  },
  "confidence": 0.0
}

Правила:
- Если просят показать записи на месяц/неделю/день/диапазон — intent=list_bookings и укажи start_date/end_date.
- Если просят показать информацию о питомце (например, «покажи Луну», даже если написано «покажу Луну») — intent=show_pet и укажи animal.name в именительном падеже.
- Если просят показать хозяина или клиента — intent=show_client. Укажи client.name, а если хозяина ищут по питомцу (например, «покажи хозяина Луны») — укажи animal.name и оставь client.name=null.
- Если просят удалить запись, отменить приём или убрать передержку — intent=delete_booking. Укажи кличку в animal.name, start_date и end_date. Для записи на один день обе даты одинаковые. Для записи на несколько дней укажи весь период; например, «удали Луну с 28 по 30 июля». Если просят все предстоящие/будущие записи конкретного питомца, установи delete_scope="upcoming" и укажи animal.name; даты в таком случае оставь null.
- Если просят записать/принесут/будет питомец — intent=create_booking.
- Если услуга не названа явно, service_type="передержка".
- Для выгула и ухода извлекай units_per_day из формулировок «2 раза в день», «три выгула», «утром и вечером». Если количество не указано, верни null.
- Для собаки извлекай размер, если он назван. «Мелкая» = мелкая; средняя и крупная возвращаются как средняя или крупная.
- Даты всегда нормализуй с годом. Если год не указан, выбери ближайшую будущую дату относительно сегодня.
- Для intent=delete_booking при неуказанном годе используй текущий год, а не будущий.
- Если пользователь отвечает "да", "подтверждаю", "согласен" — intent=answer_yes.
- Если "нет", "не тот", "создай нового" — intent=answer_no.
- Если "отмена", "отмени" — intent=cancel.
PROMPT;
    }
}
