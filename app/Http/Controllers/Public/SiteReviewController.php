<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteReview;
use App\Models\SiteSetting;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SiteReviewController extends Controller
{
    public function store(Request $request, TelegramNotificationService $telegram)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'text' => 'required|string|min:10|max:5000',
            'rating' => 'required|integer|between:1,5',
            'personal_data_consent' => 'accepted',
            'website' => 'nullable|size:0',
        ]);

        $consent = (string) (SiteSetting::first()?->personal_data_consent_text ?? '');
        if (trim(strip_tags($consent)) === '') {
            throw ValidationException::withMessages([
                'personal_data_consent' => 'Документ согласия временно недоступен.',
            ]);
        }

        if ($secret = config('services.recaptcha.secret')) {
            $request->validate(['g-recaptcha-response' => 'required|string']);
            $response = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ]);
            if (!$response->ok() || !$response->json('success')) {
                throw ValidationException::withMessages([
                    'g-recaptcha-response' => 'Подтвердите, что вы не робот.',
                ]);
            }
        }

        $review = SiteReview::create([
            'name' => trim($data['name']),
            'text' => trim($data['text']),
            'rating' => (int) $data['rating'],
            'status' => 'new',
        ]);

        $telegram->notifyConfiguredChats(
            "Новый отзыв с сайта №{$review->id}\n".
            "Имя: {$review->name}\n".
            "Оценка: {$review->rating}/5\n".
            "Текст: ".mb_substr($review->text, 0, 1200)."\n".
            "Модерация: ".route('admin.site-reviews.index')
        );

        return back()->with('review_success', 'Спасибо! Ваш отзыв отправлен на модерацию.');
    }
}
