<?php

namespace App\Http\Controllers\Operations;

use App\Enums\InternalRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\InternalRequestRequest;
use App\Http\Requests\Operations\InternalRequestReviewRequest;
use App\Models\InternalRequest;
use App\Notifications\InternalRequestStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InternalRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InternalRequest::class);

        $user = $request->user();

        $requests = InternalRequest::query()
            ->visibleTo($user)
            ->with(['requester:id,name', 'department:id,name', 'reviewedBy:id,name'])
            ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (InternalRequest $ir) => [
                ...$ir->only(['id', 'type', 'title', 'description', 'status', 'remarks', 'created_at']),
                'requester' => $ir->requester,
                'department' => $ir->department,
                'reviewed_by' => $ir->reviewedBy,
                'can_review' => $user->can('review', $ir),
                'can_cancel' => $user->can('cancel', $ir),
            ])
            ->withQueryString();

        return Inertia::render('InternalRequests/Index', [
            'requests' => $requests,
            'statuses' => collect(InternalRequestStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'filters' => ['status' => $request->string('status')->toString()],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', InternalRequest::class);

        return Inertia::render('InternalRequests/Create');
    }

    public function store(InternalRequestRequest $request): RedirectResponse
    {
        InternalRequest::create([
            ...$request->validated(),
            'requester_id' => $request->user()->id,
            'department_id' => $request->user()->department_id,
            'status' => InternalRequestStatus::Pending->value,
        ]);

        return redirect()->route('internal-requests.index')->with('success', 'Request submitted.');
    }

    public function review(InternalRequestReviewRequest $request, InternalRequest $internalRequest): RedirectResponse
    {
        $internalRequest->update([
            'status' => $request->validated('decision'),
            'remarks' => $request->validated('remarks'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $internalRequest->requester?->notify(new InternalRequestStatusChangedNotification($internalRequest));

        return back()->with('success', 'Request '.$request->validated('decision').'.');
    }

    public function cancel(Request $request, InternalRequest $internalRequest): RedirectResponse
    {
        $this->authorize('cancel', $internalRequest);

        $internalRequest->update(['status' => InternalRequestStatus::Cancelled->value]);

        return back()->with('success', 'Request cancelled.');
    }
}
