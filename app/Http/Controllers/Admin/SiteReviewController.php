<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteReview;
use Illuminate\Http\Request;

class SiteReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => 'nullable|in:new,published,hidden'])['status'] ?? 'new';
        $reviews = SiteReview::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.site_reviews.index', compact('reviews', 'status'));
    }

    public function updateStatus(Request $request, SiteReview $siteReview)
    {
        $data = $request->validate(['status' => 'required|in:new,published,hidden']);
        $siteReview->update(['status' => $data['status']]);

        return back()->with('success', 'Статус отзыва обновлён.');
    }

    public function destroy(SiteReview $siteReview)
    {
        $siteReview->delete();

        return back()->with('success', 'Отзыв удалён.');
    }
}
