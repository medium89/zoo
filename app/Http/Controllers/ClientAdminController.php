<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientAdminController extends Controller
{
    public function index()
    {
        $clients = Client::withCount(['animals', 'boardings'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $client = Client::create($this->validated($request));

        return redirect()->route('admin.clients.show', $client)->with('success', 'Клиент добавлен');
    }

    public function show(Client $client)
    {
        $client->load([
            'animals.photos',
            'animals.boardings' => fn ($query) => $query->latest('start_date'),
            'boardings.animal.photos',
        ]);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request));

        return redirect()->route('admin.clients.show', $client)->with('success', 'Клиент обновлён');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Клиент удалён');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);
    }
}
