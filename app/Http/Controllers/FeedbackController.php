<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $recaptchaSecret = config('services.recaptcha.secret');
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'message' => 'required|string',
        ];
        if ($recaptchaSecret) {
            $rules['g-recaptcha-response'] = 'required|string';
        }

        $request->validate($rules);

        if (!$recaptchaSecret) {
            return back()->withErrors(['g-recaptcha-response' => 'reCAPTCHA не настроена, обратитесь к администратору'])->withInput();
        }

        $verification = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        if (!$verification->ok() || !$verification->json('success')) {
            return back()->withErrors(['g-recaptcha-response' => 'Подтвердите, что вы не робот'])->withInput();
        }

        Feedback::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'message' => $request->message,
            'status' => 'new',
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

        return back()->with('success', 'Ваше сообщение успешно отправлено!');
    }

    public function index()
    {
        $feedbacks = Feedback::latest()->paginate(10);
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
}
