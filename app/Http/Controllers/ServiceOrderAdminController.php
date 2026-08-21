<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAnimal;
use Illuminate\Http\Request;

class ServiceOrderAdminController extends Controller
{
    public function index()
    {
        $orders = ServiceOrder::with(['client', 'animals.category', 'animals.animal'])
            ->whereNull('archived_at')
            ->orderBy('start_date')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.service-orders.index', [
            'orders' => $orders,
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'service_type' => 'required|in:передержка,выгул,уход',
            'units_per_day' => 'required|integer|min:1|max:24',
            'daily_price' => 'required|integer|min:0|max:100000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'address' => 'nullable|string|max:2000',
            'note' => 'nullable|string|max:5000',
            'animals' => 'nullable|array',
            'animals.*.label' => 'nullable|string|max:255',
            'animals.*.quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $serviceOrder->update(collect($data)->except('animals')->all());

        if (array_key_exists('animals', $data)) {
            $serviceOrder->animals()->delete();
            foreach ($data['animals'] as $animal) {
                $label = trim((string) ($animal['label'] ?? ''));
                if ($label !== '') {
                    $serviceOrder->animals()->create([
                        'label' => $label,
                        'quantity' => $animal['quantity'] ?? 1,
                    ]);
                }
            }
        }

        return back()->with('success', 'Заказ обновлён');
    }

    public function archive(ServiceOrder $serviceOrder)
    {
        $serviceOrder->update(['archived_at' => now(), 'status' => 'archived']);

        return back()->with('success', 'Заказ перенесён в архив');
    }

    public function destroy(ServiceOrder $serviceOrder)
    {
        $serviceOrder->delete();

        return back()->with('success', 'Заказ удалён');
    }
}
