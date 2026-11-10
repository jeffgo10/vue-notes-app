<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Note::latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(['title' => 'required', 'content' => 'required']);

        $data = $request->only(['title', 'content']);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        return Note::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Note $note)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $data = $request->only(['title', 'content']);

        if ($request->hasFile('attachment')) {
            // 1. Delete the old file if one existed
            if ($note->attachment) {
                Storage::disk('public')->delete($note->attachment);
            }

            // 2. Store the new file on the 'public' disk
            $data['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $note->update($data);

        return $note;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        if ($note->attachment) {
            Storage::disk('public')->delete($note->attachment);
        }
        $note->delete();
        return response()->noContent();
    }
}
