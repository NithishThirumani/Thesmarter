<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\UserContact;
use App\ContactDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Log;

class UserAddressController extends Controller
{
    // -------------------------------------------------------------------------
    // ADDRESS CRUD
    // -------------------------------------------------------------------------

    /**
     * GET /api/users/{user_id}/addresses
     * Get all addresses for a user.
     */
    public function getAddresses($user_id)
    {
        $addresses = UserContact::where('user_id', $user_id)
            ->with('contactDetails')
            ->orderBy('contact_id', 'desc') // last added first
            ->get()
            ->map(fn($uc) => $this->formatAddress($uc));

        return response()->json([
            'success' => true,
            'data'    => $addresses,
        ]);
    }

    /**
     * POST /api/users/{user_id}/addresses
     * Add a new address for a user.
     */
    public function addAddress(Request $request, $user_id)
    {
        $validator = Validator::make($request->all(), [
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'address1'     => 'required|string|max:500',
            'area'         => 'nullable|string|max:255',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:255',
            'pincode'      => 'required|string|max:20',
            'country'      => 'required|string|max:100',
            'contact_type' => 'nullable|string|max:50',   // e.g. home|work|other
            'latitude'     => 'nullable|numeric|between:-90,90',
            'longitude'    => 'nullable|numeric|between:-180,180',
            'make_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $user_id, &$result) {
            $contact = ContactDetail::create([
                'phone'     => $request->phone,
                'email'     => $request->email,
                'address1'  => $request->address1,
                'area'      => $request->area,
                'city'      => $request->city,
                'state'     => $request->state,
                'pincode'   => $request->pincode,
                'country'   => $request->country,
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // If making this default, clear existing default first
            if ($request->boolean('make_default')) {
                UserContact::where('user_id', $user_id)->update(['default_contact' => 0]);
            }

            $userContact = UserContact::create([
                'user_id'         => $user_id,
                'contact_id'      => $contact->contact_id,
                'contact_type'    => $request->input('contact_type', 'otherss'),
                'default_contact' => $request->boolean('make_default') ? 1 : 0,
            ]);

            $result = $this->formatAddress($userContact->load('contactDetails'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data'    => $result,
        ], 201);
    }

    /**
     * PUT /api/users/{user_id}/addresses/{contact_id}
     * Update an existing address.
     */
    public function updateAddress(Request $request, $user_id, $contact_id)
    {
        $userContact = UserContact::where('user_id', $user_id)
            ->where('contact_id', $contact_id)
            ->first();

        if (!$userContact) {
            return response()->json(['success' => false, 'message' => 'Address not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'address1'     => 'sometimes|string|max:500',
            'area'         => 'nullable|string|max:255',
            'city'         => 'sometimes|string|max:100',
            'pincode'      => 'sometimes|string|max:20',
            'country'      => 'sometimes|string|max:100',
            'contact_type' => 'nullable|string|max:50',
            'latitude'     => 'nullable|numeric|between:-90,90',
            'longitude'    => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $userContact, $contact_id) {
            ContactDetail::where('contact_id', $contact_id)->update(
                array_filter($request->only([
                    'phone',
                    'email',
                    'address1',
                    'area',
                    'city',
                    'pincode',
                    'country',
                    'latitude',
                    'longitude',
                ]), fn($v) => !is_null($v))
            );

            if ($request->has('contact_type')) {
                $userContact->update(['contact_type' => $request->contact_type]);
            }
        });

        $updated = $userContact->fresh()->load('contactDetails');

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data'    => $this->formatAddress($updated),
        ]);
    }

    /**
     * DELETE /api/users/{user_id}/addresses/{contact_id}
     * Remove an address from the user.
     */
    public function deleteAddress($user_id, $contact_id)
    {
        $userContact = UserContact::where('user_id', $user_id)
            ->where('contact_id', $contact_id)
            ->first();

        if (!$userContact) {
            return response()->json(['success' => false, 'message' => 'Address not found.'], 404);
        }

        DB::transaction(function () use ($userContact, $contact_id) {
            $wasDefault = $userContact->default_contact;
            $userId     = $userContact->user_id;

            $userContact->delete();
            ContactDetail::where('contact_id', $contact_id)->delete();

            // Auto-promote the most recently added address as default if needed
            if ($wasDefault) {
                $next = UserContact::where('user_id', $userId)
                    ->orderByDesc('contact_id')
                    ->first();
                if ($next) {
                    $next->update(['default_contact' => 1]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/users/{user_id}/addresses/{contact_id}/set-default
     * Mark one address as primary/default.
     */
    public function setDefaultAddress($user_id, $contact_id)
    {
        try {

            $userContact = UserContact::where('user_id', $user_id)
                ->where('contact_id', $contact_id)
                ->first();

            if (!$userContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.'
                ], 404);
            }

            DB::transaction(function () use ($user_id, $userContact) {

                // Reset all addresses
                UserContact::where('user_id', $user_id)
                    ->update(['default_contact' => 0]);

                // Set selected address as default
                $userContact->update([
                    'default_contact' => 1
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Default address updated.'
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // HOME SCREEN — primary address (optimised, single query)
    // -------------------------------------------------------------------------

    /**
     * GET /api/users/{user_id}/primary-address
     * Returns ONLY the default address. Heavily cached for speed.
     * Called on every app home-screen load.
     */
    public function getPrimaryAddress($user_id)
    {
        // Raw join = single round-trip, no N+1, no ORM overhead
        $address = DB::table('user_contact as uc')
            ->join('contact_detail as cd', 'uc.contact_id', '=', 'cd.contact_id')
            ->where('uc.user_id', $user_id)
            ->where('uc.default_contact', 1)
            ->select(
                'cd.contact_id',
                'uc.contact_type',
                'cd.phone',
                'cd.email',
                'cd.address1',
                'cd.area',
                'cd.city',
                'cd.pincode',
                'cd.country',
                'cd.latitude',
                'cd.longitude'
            )
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'No primary address found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $address,
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    private function formatAddress(UserContact $uc): array
    {
        $cd = $uc->contactDetails;
        return [
            'contact_id'      => $uc->contact_id,
            'contact_type'    => $uc->contact_type,
            'default_contact' => (bool) $uc->default_contact,
            'phone'           => $cd->phone ?? null,
            'email'           => $cd->email ?? null,
            'address1'        => $cd->address1 ?? null,
            'area'            => $cd->area ?? null,
            'city'            => $cd->city ?? null,
            'state'           => $cd->state ?? "",
            'pincode'         => $cd->pincode ?? null,
            'country'         => $cd->country ?? null,
            'latitude'        => $cd->latitude ?? null,
            'longitude'       => $cd->longitude ?? null,
        ];
    }
}
