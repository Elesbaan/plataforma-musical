<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function edit(SiteSettingService $service): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $service->editable(),
        ]);
    }

    public function update(UpdateSiteSettingRequest $request, SiteSettingService $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('admin.settings.edit')->with('success', 'La identidad de tu marca se guardó correctamente.');
    }
}
