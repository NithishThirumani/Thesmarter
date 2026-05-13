<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Comments;
use App\UniversalNotes;
use App\Http\Resources\CommentsResource;

class CommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($note)
    {
        // Show comments of the respective notes 
        
        $notes = UniversalNotes::with('comments','comments.user:first_name,last_name,user_id')->where('note_id', $note)->first();
        // $notes = UniversalNotes::with(array('comments'=>function($query){
        //     $query->orderBy('created_at','DESC');
        // }),'comments.user:first_name,last_name,user_id')
        // ->where('note_id', $note)->first();
        return response()->json($notes);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 
        try {
            $request->validate([
                'note_id' => 'bail|integer|required',
                'user_id' => 'integer|required',
                'comment' => 'string|required'
            ]);
            $data = $request->all();
            $commentData = array(
                'note_id' => $data['note_id'],
                'user_id' => $data['user_id'],
                'comment' => $data['comment'],
                'parent_comment_id' => null
            );
            $comment = Comments::create($commentData);
            return response()->json(['comment' => new CommentsResource($comment), 'message' => 'Comment added successfully', 'error_flag' => false]);
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comments $comment)
    {
        //
       
        try {
            $data = $request->all();
            $request->validate([
                'user_id' => 'integer|required',
                'comment' => 'string|required'
            ]);
           
            $comment->update($data);

            return response()->json(['comment' => new CommentsResource($comment), 'message' => 'Comment updated successfully', 'error_flag' => false]);
        } catch (Exception $execption) {
            print_r($execption->getMessage());
         }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comments $comment)
    {
        // Need to validate is same user is deleting the comment or someone else 
        // If you type is Admin then delete 
        // If user user is the same as the one who has put the comment then delete 

        $comment->delete();
        return response()->json(['error_flag' => false, 'message' => 'Comment deleted succcessfully']);
    }
}
