<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected static $MIN_SEARCH_LENGTH = 3;

    protected static $SEARCH_LIMIT = 10;

    /**
     * Search users of any role by name or email for the task assignee dropdown. Admin only.
     */
    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $keyword = trim((string) $request->input('q'));

        if (mb_strlen($keyword) < self::$MIN_SEARCH_LENGTH) {
            return response()->json([]);
        }

        $users = User::select(['id', 'name', 'email'])
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(self::$SEARCH_LIMIT)
            ->get();

        return response()->json($users);
    }
}
