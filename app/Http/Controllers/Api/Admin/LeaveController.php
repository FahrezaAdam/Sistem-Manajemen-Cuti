<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class LeaveController extends Controller
{
    #[OA\Get(
        path: "/api/admin/leaves",
        tags: ["Admin Leaves"],
        summary: "Get all leaves",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index()
    {
        $leaves = Leave::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($leaves);
    }

    #[OA\Patch(
        path: "/api/admin/leaves/{id}/status",
        tags: ["Admin Leaves"],
        summary: "Approve or reject a leave",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["approved", "rejected"], example: "approved")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function update(Request $request, Leave $leaf)
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($request->status === 'approved' && $leaf->status !== 'approved') {
            $user = $leaf->user;
            $start = \Carbon\Carbon::parse($leaf->start_date);
            $end = \Carbon\Carbon::parse($leaf->end_date);
            $days = $start->diffInDays($end) + 1;

            if ($user->leave_quota < $days) {
                return response()->json(['message' => 'Karyawan ini tidak memiliki sisa kuota yang cukup untuk menyetujui cuti ini'], 400);
            }
            $user->decrement('leave_quota', $days);
        }

        $leaf->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Leave status updated successfully',
            'leave' => $leaf
        ]);
    }
}
