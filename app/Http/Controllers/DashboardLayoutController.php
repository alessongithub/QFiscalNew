<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDashboardLayout;

class DashboardLayoutController extends Controller
{
    public function save(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'container' => 'required|string|max:100',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
        ]);

        $pref = UserDashboardLayout::updateOrCreate(
            ['user_id' => $user->id, 'container_name' => $data['container']],
            [
                'x_position' => $data['x'],
                'y_position' => $data['y'],
                'width' => $data['width'],
                'height' => $data['height'],
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function reset(Request $request)
    {
        $user = $request->user();
        UserDashboardLayout::where('user_id', $user->id)->delete();
        // Nada para recalcular aqui; a view cairá no layout padrão sem posições absolutas
        return back()->with('success', 'Layout restaurado para o padrão.');
    }
}


