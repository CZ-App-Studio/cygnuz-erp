<?php

namespace App\Http\Controllers;

use App\Models\UserMenuPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuPreferenceController extends Controller
{
    /**
     * Toggle the pin status of a menu item
     */
    public function togglePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menu_slug' => 'required|string',
        ]);

        $userId = Auth::id();
        $menuSlug = $validated['menu_slug'];

        $preference = UserMenuPreference::firstOrNew([
            'user_id' => $userId,
            'menu_slug' => $menuSlug,
        ]);

        // Toggle the pin status
        $preference->is_pinned = !$preference->is_pinned;

        // If pinning for the first time, set the display order to the highest + 1
        if ($preference->is_pinned && !$preference->exists) {
            $maxOrder = UserMenuPreference::where('user_id', $userId)
                ->where('is_pinned', true)
                ->max('display_order') ?? 0;
            $preference->display_order = $maxOrder + 1;
        }

        $preference->save();

        return response()->json([
            'success' => true,
            'is_pinned' => $preference->is_pinned,
            'display_order' => $preference->display_order,
        ]);
    }

    /**
     * Update the display order of pinned menu items
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.menu_slug' => 'required|string',
            'orders.*.display_order' => 'required|integer|min:0',
        ]);

        $userId = Auth::id();

        foreach ($validated['orders'] as $item) {
            UserMenuPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'menu_slug' => $item['menu_slug'],
                ],
                [
                    'is_pinned' => true,
                    'display_order' => $item['display_order'],
                ]
            );
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get all pinned menu items for the current user
     */
    public function getPinned(): JsonResponse
    {
        $userId = Auth::id();

        $pinnedMenus = UserMenuPreference::where('user_id', $userId)
            ->where('is_pinned', true)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
            'pinned_menus' => $pinnedMenus,
        ]);
    }
}
