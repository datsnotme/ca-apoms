<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extension\ExtensionBeneficiaryRequest;
use App\Models\ExtensionBeneficiary;
use App\Models\ExtensionProject;
use Illuminate\Http\RedirectResponse;

class ExtensionBeneficiaryController extends Controller
{
    public function store(ExtensionBeneficiaryRequest $request, ExtensionProject $extensionProject): RedirectResponse
    {
        $extensionProject->beneficiaries()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Beneficiary recorded.');
    }

    public function destroy(ExtensionProject $extensionProject, ExtensionBeneficiary $beneficiary): RedirectResponse
    {
        abort_unless($beneficiary->extension_project_id === $extensionProject->id, 404);

        $this->authorize('delete', $beneficiary);

        $beneficiary->delete();

        return back()->with('success', 'Beneficiary removed.');
    }
}
