<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class PersonalDataConsentController extends Controller
{
    public function edit()
    {
        $settings = SiteSetting::first() ?? new SiteSetting();

        return view('admin.personal_data_consent.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'personal_data_consent_text' => 'required|string',
        ]);

        $settings = SiteSetting::first() ?? new SiteSetting();
        $settings->personal_data_consent_text = $data['personal_data_consent_text'];
        $settings->save();

        return redirect()
            ->route('admin.personal-data-consent.edit')
            ->with('success', 'Документ согласия сохранен');
    }
}

