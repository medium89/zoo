<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\Category;
use App\Models\Client;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;

class ServiceOrderAdminController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['client', 'category'])->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.service-orders.index', [
            'orders' => ServiceOrder::with(['client', 'animals.services', 'animals.category', 'animals.animal.photos'])->whereNull('archived_at')->orderBy('start_date')->orderByDesc('created_at')->get(),
            'clients' => Client::orderBy('name')->get(),
            'animals' => $animals,
            'categories' => $categories,
            'animalsPayload' => $animals->map(fn (Animal $animal) => ['id' => $animal->id, 'name' => $animal->name, 'category_id' => $animal->category_id, 'client' => $animal->client?->name])->values()->all(),
            'categoriesPayload' => $categories->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $order = ServiceOrder::create($this->orderAttributes($data));
        $this->syncDetails($order, $data);
        return back()->with('success', 'Заказ добавлен');
    }

    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        $data = $this->validated($request);
        $serviceOrder->update($this->orderAttributes($data));
        $this->syncDetails($serviceOrder, $data);
        return back()->with('success', 'Заказ обновлён');
    }

    public function archive(ServiceOrder $serviceOrder)
    {
        $serviceOrder->update(['archived_at' => now(), 'status' => 'archived']);
        $this->syncLegacyBoarding($serviceOrder);
        return back()->with('success', 'Заказ перенесён в архив');
    }

    public function destroy(ServiceOrder $serviceOrder)
    {
        $serviceOrder->delete();
        return back()->with('success', 'Заказ удалён');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date',
            'address' => 'nullable|string|max:2000', 'note' => 'nullable|string|max:5000', 'animals' => 'required|array|min:1',
            'animals.*.animal_id' => 'nullable|exists:animals,id', 'animals.*.name' => 'nullable|string|max:255',
            'animals.*.category_id' => 'nullable|exists:categories,id', 'animals.*.quantity' => 'nullable|integer|min:1|max:99',
            'animals.*.note' => 'nullable|string|max:1000', 'animals.*.services' => 'required|array|min:1|max:3',
            'animals.*.services.*.service_type' => 'required|distinct|in:передержка,выгул,уход',
            'animals.*.services.*.units_per_day' => 'required|integer|min:1|max:24', 'animals.*.services.*.unit_price' => 'required|integer|min:0|max:100000',
        ]);
    }

    private function orderAttributes(array $data): array
    {
        $services = collect($data['animals'])->flatMap(fn (array $animal) => collect($animal['services'])->map(fn (array $service) => $service + ['quantity' => $animal['quantity'] ?? 1]))->values();
        $first = $services->first();
        return ['client_id' => $data['client_id'] ?? null, 'service_type' => $first['service_type'], 'units_per_day' => $first['units_per_day'],
            'daily_price' => $services->sum(fn (array $service) => $service['quantity'] * $service['units_per_day'] * $service['unit_price']),
            'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'address' => $data['address'] ?? null, 'note' => $data['note'] ?? null];
    }

    private function syncDetails(ServiceOrder $order, array $data): void
    {
        $order->services()->delete();
        $order->animals()->delete();
        foreach ($data['animals'] ?? [] as $position) {
            if (empty($position['animal_id']) && trim((string) ($position['name'] ?? '')) === '' && empty($position['category_id'])) { continue; }
            $animal = !empty($position['animal_id']) ? Animal::find($position['animal_id']) : null;
            if (!$animal && trim((string) ($position['name'] ?? '')) !== '') {
                $category = Category::find($position['category_id'] ?? null);
                $animal = Animal::firstOrCreate(['name' => trim($position['name']), 'client_id' => $order->client_id], ['category_id' => $category?->id, 'species' => $category?->name, 'order' => (int) Animal::max('order') + 1]);
            }
            if ($animal && $order->client_id && !$animal->client_id) { $animal->update(['client_id' => $order->client_id]); }
            $orderAnimal = $order->animals()->create(['animal_id' => $animal?->id, 'category_id' => $animal?->category_id ?: ($position['category_id'] ?? null),
                'label' => $animal?->name ?: trim((string) ($position['name'] ?? '')) ?: null, 'quantity' => $position['quantity'] ?? 1, 'note' => $position['note'] ?? null]);
            foreach ($position['services'] as $service) {
                $orderAnimal->services()->create($service + ['service_order_id' => $order->id]);
            }
        }
        $this->syncLegacyBoarding($order->fresh('animals'));
    }

    private function syncLegacyBoarding(ServiceOrder $order): void
    {
        if (!$order->legacy_boarding_id || !($boarding = Boarding::find($order->legacy_boarding_id))) { return; }
        $firstAnimal = $order->animals()->with(['animal', 'services'])->first(); $firstService = $firstAnimal?->services->first();
        $boarding->update(['client_id' => $order->client_id, 'animal_id' => $firstAnimal?->animal_id, 'name' => $firstAnimal?->animal?->name ?: $firstAnimal?->label ?: $boarding->name,
            'service_type' => $firstService?->service_type ?: $order->service_type, 'units_per_day' => $firstService?->units_per_day ?: $order->units_per_day,
            'unit_price' => $firstService?->unit_price ?: $order->daily_price, 'start_date' => $order->start_date, 'end_date' => $order->end_date,
            'note' => $order->note, 'archived_at' => $order->archived_at]);
    }
}
