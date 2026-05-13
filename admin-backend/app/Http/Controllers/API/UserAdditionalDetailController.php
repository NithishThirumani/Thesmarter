<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\UserAdditionalDetail;
use Illuminate\Http\Request;

class UserAdditionalDetailController extends Controller
{
    // Store new details
    public function store(Request $request)
    {

        try {
            $validated = $request->validate([
                'user_id' => 'required',
                'details' => 'nullable|array',
            ], [
                'user_id.required' => 'The user ID is required.',
                'user_id.exists' => 'The user ID does not exist.',
                'details.array' => 'The details field must be an array.',
            ]);

            // Ensure each detail has 'value' and 'is_printable'
            foreach ($validated['details'] as $key => $value) {
                if (!array_key_exists('value', $value) || !array_key_exists('is_printable', $value)) {
                    return response()->json([
                        'error' => "Detail at index '{$key}' must contain both 'value' and 'is_printable' fields."
                    ], 400);
                }
            }

            $detail = UserAdditionalDetail::updateOrCreate(
                ['user_id' => $validated['user_id']],
                ['details' => $validated['details']]
            );
            $response = array(
                'user_id' => $detail['user_id'],
                'details' => $detail['details'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Details added/updated successfully',
                'data' => $response
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'errors' => $e->errors()
            ], 422);
        }
    }

    // Show specific user details
    public function show($id)
    {
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }
        $detail = UserAdditionalDetail::where('user_id', $id)->first();

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'No details found for the user'
            ], 404);
        }
        $response = array(
            'user_id' => $detail['user_id'],
            'details' => $detail['details'],
        );

        return response()->json([
            'success' => true,
            'message' => 'User additional details list',
            'data' => $response
        ], 201);
    }

    // Update user details
    public function update(Request $request, $id)
    {
        try {
            $detail = UserAdditionalDetail::where('user_id', $id)->firstOrFail();
            $validated = $request->validate([
                'details' => 'sometimes|required',
            ], [
                'details.required' => 'The details field is required.',
                'details.array' => 'The details field must be an array.',
            ]);

            foreach ($validated['details'] as $key => $value) {
                if (!isset($value['value']) || !isset($value['is_printable'])) {
                    return response()->json([
                        'error' => "Each detail key must contain 'value' and 'is_printable' fields."
                    ], 400);
                }
            }

            $detail->update(['details' => $validated['details']]);

            return response()->json(['message' => 'Details updated successfully', 'data' => $detail], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User details not found'], 404);
        }
    }


    // Delete user details
    public function destroy($id)
    {
        $detail = UserAdditionalDetail::where('user_id', $id)->first();

        if (!$detail) {
            return response()->json(['message' => 'No details found for the user'], 404);
        }

        $detail->delete();

        return response()->json(['message' => 'Details deleted successfully'], 200);
    }
}
