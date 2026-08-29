<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\Feedback;
use App\Models\SiteSetting;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function store(Request $request, TelegramNotificationService $telegram)
    {
        $siteSettings = SiteSetting::first();
        $consentTextHtml = (string)($siteSettings?->personal_data_consent_text ?? '');
        if (trim(strip_tags($consentTextHtml)) === '') {
            throw ValidationException::withMessages([
                'personal_data_consent' => 'Документ согласия на обработку персональных данных не настроен. Попробуйте позже.',
            ]);
        }

        $recaptchaSecret = config('services.recaptcha.secret');
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'message' => 'required|string',
            'personal_data_consent' => 'accepted',
        ];
        if ($recaptchaSecret) {
            $rules['g-recaptcha-response'] = 'required|string';
        }

        $request->validate($rules, [
            'personal_data_consent.accepted' => 'Для отправки заявки необходимо согласие на обработку персональных данных.',
        ]);

        if (!$recaptchaSecret) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'reCAPTCHA не настроена, обратитесь к администратору',
            ]);
        }

        $verification = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        if (!$verification->ok() || !$verification->json('success')) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Подтвердите, что вы не робот',
            ]);
        }

        Feedback::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'message' => $request->message,
            'status' => 'new',
            'order' => (int)Feedback::max('order') + 1,
            'personal_data_consent' => true,
            'personal_data_consent_at' => now(),
            'personal_data_consent_text' => $consentTextHtml,
            'personal_data_consent_hash' => hash('sha256', $consentTextHtml),
        ]);

        $text = "Новая заявка с сайта:\n";
        $text .= "Имя: {$request->name}\n";
        $text .= "Телефон: {$request->phone}\n";
        $text .= "Сообщение: {$request->message}";

        $telegram->notifyConfiguredChats($text);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ваше сообщение успешно отправлено!',
            ]);
        }

        return back()->with('success', 'Ваше сообщение успешно отправлено!');
    }

}
