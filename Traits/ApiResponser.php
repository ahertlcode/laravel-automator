<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
/*
 |-------------------------------------------
 | Api Response Trait
 |-------------------------------------------
 |
 | This trait will be used for any response we sent to client
 |
 */

 trait ApiResponser 
 {
    /**
     * Return a success JSON response.
     * 
     * @param array|string $data
     * @param string $message
     * @param int|null $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data, string $message = null, int $code = 200){
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return an error JSON response
     * 
     * @param string $message
     * @param int $code
     * @param array|string|null $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = null, int $code, $data = null){
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return the currently login user
     * An instance of App\User model
     * @return App\User
     */
    protected function currentUser()
    {
        return auth()->user();
    }

    /**
     * Get the main account owner
     * for the currently authenticated
     * user @return App\User
     */
    protected function getDataOwner() {
        if ($this->currentUser()->owner_id !== null) {
            return User::where('id', $this->currentUser()->owner_id)->get()[0];
        } else {
            return $this->currentUser();
        }
    }
}
?>