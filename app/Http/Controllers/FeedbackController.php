<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function store(Request $request)
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

        $token = env('TELEGRAM_BOT_TOKEN');
        $chatIds = [
            env('TELEGRAM_CHAT_ID'),
            env('TELEGRAM_CHAT_ID_2')
        ];

        foreach ($chatIds as $id) {
            if ($token && $id) {
                Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $id,
                    'text'    => $text,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ваше сообщение успешно отправлено!',
            ]);
        }

        return back()->with('success', 'Ваше сообщение успешно отправлено!');
    }

    public function index()
    {
        $feedbacks = Feedback::orderBy('order')->latest()->paginate(10);
        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    public function edit(Feedback $feedback)
    {
        return view('admin.feedbacks.edit', compact('feedback'));
    }

    public function update(Request $request, Feedback $feedback)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|string|in:new,in_progress,completed,cancelled',
        ]);

        $feedback->update($request->all());

        return redirect()->route('admin.feedbacks.index')->with('success', 'Feedback updated successfully!');
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return redirect()->route('admin.feedbacks.index')->with('success', 'Feedback deleted successfully!');
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('orders', []) as $id => $order) {
            if ($model = Feedback::find($id)) {
                $model->order = (int)$order;
                $model->save();
            }
        }
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($model = Feedback::find($id)) {
                $model->status = $status;
                $model->save();
            }
        }

        return back()->with('success', 'Изменения сохранены');
    }
}
