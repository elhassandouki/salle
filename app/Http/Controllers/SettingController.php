<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\AdminLteMenuBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $defaultMenuJson = AdminLteMenuBuilder::getDefaultMenuJson();
        $savedMenuJson = $settings[AdminLteMenuBuilder::MENU_SETTING_KEY] ?? $defaultMenuJson;

        return view('settings.index', compact('settings', 'defaultMenuJson', 'savedMenuJson'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'value' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Setting::set($request->key, $request->value);

            return response()->json([
                'success' => true,
                'message' => 'Paramètre enregistré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Setting $setting)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting->update(['value' => $request->value]);

            return response()->json([
                'success' => true,
                'message' => 'Paramètre mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
