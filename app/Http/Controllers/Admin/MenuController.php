<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Directory (on the public disk) where menu photos are stored.
     */
    private const FOTO_DIRECTORY = 'menu-foto';

    /**
     * Display a filtered and paginated listing of menu.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $menu = Menu::query()
            ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
            ->when(
                in_array($status, [Menu::STATUS_ACTIVE, Menu::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.menu.index', [
            'menu' => $menu,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new menu.
     */
    public function create(): View
    {
        return view('admin.menu.create');
    }

    /**
     * Store a newly created menu.
     */
    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store(self::FOTO_DIRECTORY, 'public');

            if ($path === false) {
                return back()->withInput()->withErrors(['foto' => 'Gagal mengunggah foto.']);
            }

            $data['foto'] = $path;
        }

        Menu::create($data);

        return redirect()->route('admin.menu.index')->with('status', 'Menu berhasil ditambahkan.');
    }

    /**
     * Display the specified menu.
     */
    public function show(Menu $menu): View
    {
        return view('admin.menu.show', ['menu' => $menu]);
    }

    /**
     * Show the form for editing the specified menu.
     */
    public function edit(Menu $menu): View
    {
        return view('admin.menu.edit', ['menu' => $menu]);
    }

    /**
     * Update the specified menu.
     */
    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store(self::FOTO_DIRECTORY, 'public');

            if ($path === false) {
                return back()->withInput()->withErrors(['foto' => 'Gagal mengunggah foto.']);
            }

            if ($menu->foto) {
                Storage::disk('public')->delete($menu->foto);
            }

            $data['foto'] = $path;
        }

        $menu->update($data);

        return redirect()->route('admin.menu.index')->with('status', 'Menu berhasil diperbarui.');
    }

    /**
     * Remove the specified menu.
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        if ($menu->produksis()->exists()) {
            return redirect()->route('admin.menu.index')
                ->with('error', 'Menu tidak dapat dihapus karena sudah digunakan pada data produksi.');
        }

        $menu->delete();

        if ($menu->foto) {
            Storage::disk('public')->delete($menu->foto);
        }

        return redirect()->route('admin.menu.index')->with('status', 'Menu berhasil dihapus.');
    }
}
