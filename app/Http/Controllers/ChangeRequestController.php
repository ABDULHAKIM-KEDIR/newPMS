<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;

class ChangeRequestController extends Controller
{
    public function approve(ChangeRequest $changeRequest)
    {
        $this->decide($changeRequest, 'Approved');

        return back()->with('status', 'Change request approved.');
    }

    public function reject(ChangeRequest $changeRequest)
    {
        $this->decide($changeRequest, 'Rejected');

        return back()->with('status', 'Change request rejected.');
    }

    private function decide(ChangeRequest $changeRequest, string $decision): void
    {
        $user = Auth::user();
        abort_unless($user->can('approve_change_requests'), 403);

        $changeRequest->update([
            'status' => $decision,
            'approved_by' => $user->user_id,
            'approved_date' => now(),
        ]);

        Activity::log("{$decision} change request", 'ChangeRequest', $changeRequest->change_request_id, $changeRequest->description);
        Activity::notify(
            $changeRequest->requested_by,
            "Your change request was {$decision}: \"{$changeRequest->description}\"",
            'approval'
        );
    }
}
