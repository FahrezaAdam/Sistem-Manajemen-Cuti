<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class LeaveController extends Controller
{
    #[OA\Get(
        path: "/api/leaves",
        tags: ["Employee Leaves"],
        summary: "Get list of leaves for the current employee",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index(Request $request)
    {
        $leaves = $request->user()->leaves()->orderBy('created_at', 'desc')->get();
        return response()->json($leaves);
    }

    #[OA\Post(
        path: "/api/leaves",
        tags: ["Employee Leaves"],
        summary: "Submit a new leave request",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["start_date", "end_date", "reason", "attachment"],
                    properties: [
                        new OA\Property(property: "start_date", type: "string", format: "date", example: "2026-08-01"),
                        new OA\Property(property: "end_date", type: "string", format: "date", example: "2026-08-05"),
                        new OA\Property(property: "reason", type: "string", example: "Vacation"),
                        new OA\Property(property: "attachment", type: "string", format: "binary")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Leave request submitted successfully")
        ]
    )]
    public function store(StoreLeaveRequest $request)
    {
        $user = $request->user();
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $daysRequested = $start->diffInDays($end) + 1; // Inclusive

        if ($user->leave_quota < $daysRequested) {
            return response()->json([
                'message' => 'Insufficient leave quota.',
                'quota_remaining' => $user->leave_quota,
                'days_requested' => $daysRequested
            ], 400);
        }

        $path = $request->file('attachment')->store('leave_attachments', 'public');

        $leave = $user->leaves()->create([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'attachment_path' => $path,
            'status' => 'pending', // default
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully.',
            'leave' => $leave,
            'quota_remaining' => $user->leave_quota
        ], 201);
    }

    #[OA\Get(
        path: "/api/leaves/{id}",
        tags: ["Employee Leaves"],
        summary: "Get a specific leave request",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function show(Request $request, Leave $leaf)
    {
        if ($leaf->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($leaf);
    }
}
