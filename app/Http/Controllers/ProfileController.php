<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        Log::info('Profile update request received', [
            'user_id' => $request->user()->id,
            'has_file' => $request->hasFile('tenant_logo'),
            'files' => array_keys($request->allFiles()),
        ]);
        $validated = $request->validated();
        
        // Atualiza dados do usuário (somente campos presentes)
        $userData = [
            'email' => $validated['email'],
        ];
        if (array_key_exists('name', $validated)) {
            $userData['name'] = $validated['name'];
        }
        $request->user()->fill($userData);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // Update tenant data
        $tenant = $request->user()->tenant;
        if ($tenant) {
            $tenant->fill([
                'phone' => $validated['phone'] ?? $tenant->phone,
                'zip_code' => $validated['zip_code'] ?? $tenant->zip_code,
                'address' => $validated['address'] ?? $tenant->address,
                'number' => $validated['number'] ?? $tenant->number,
                'complement' => $validated['complement'] ?? $tenant->complement,
                'neighborhood' => $validated['neighborhood'] ?? $tenant->neighborhood,
                'city' => $validated['city'] ?? $tenant->city,
                'state' => $validated['state'] ?? $tenant->state,
            ]);

            // Handle logo upload
            if ($request->hasFile('tenant_logo')) {
                $file = $request->file('tenant_logo');
                if ($file->isValid()) {
                    try {
                        // Remove old logo if exists
                        if ($tenant->logo_path) {
                            \Storage::disk('public')->delete($tenant->logo_path);
                        }
                        
                        $path = $file->store('tenants/logos/'.$tenant->id, 'public');
                        $tenant->logo_path = $path;
                        Log::info('Tenant logo stored', [
                            'tenant_id' => $tenant->id,
                            'path' => $path,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error uploading tenant logo', [
                            'tenant_id' => $tenant->id,
                            'error' => $e->getMessage(),
                        ]);
                        return back()->withErrors(['tenant_logo' => 'Erro ao fazer upload da logo. Por favor, tente novamente.']);
                    }
                } else {
                    Log::warning('Invalid tenant logo uploaded', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return back()->withErrors(['tenant_logo' => 'Arquivo inválido. Por favor, selecione uma imagem JPG ou PNG.']);
                }
            }

            $tenant->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
