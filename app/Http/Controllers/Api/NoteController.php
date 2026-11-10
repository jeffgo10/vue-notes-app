<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

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
     * Record and verify on-chain notarization for a note.
     */
    public function notarize(Request $request, Note $note)
    {
        $validated = $request->validate([
            'tx_hash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'wallet_address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);
        $currentHash = $note->current_hash; // 0x + 64 hex chars
        $rpcUrl = config('services.blockchain.rpc_url');
        $contractAddress = config('services.blockchain.contract_address');
        // Function selector for `verify(bytes32)` = keccak256("verify(bytes32)")[0..4] = 0x75ab9782
        $dataPayload = '0x75e36616' . substr($currentHash, 2);
        $response = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'eth_call',
            'params' => [
                [
                    'to' => $contractAddress,
                    'data' => $dataPayload,
                ],
                'latest',
            ],
        ]);
        $resultHex = $response->json('result');
        // Result is 64 bytes (128 hex chars): [32-byte address][32-byte timestamp]
        if (!$resultHex || $resultHex === '0x' || strlen($resultHex) < 130) {
            return response()->json(['message' => 'Could not verify hash on-chain.'], 422);
        }
        $rawHex = substr($resultHex, 2);
        $onChainAddress = '0x' . substr($rawHex, 24, 40);
        $onChainTimestamp = hexdec(substr($rawHex, 64, 64));
        if ($onChainTimestamp === 0) {
            return response()->json(['message' => 'Note hash is not notarized on-chain yet.'], 422);
        }
        $note->update([
            'notarized_hash' => $currentHash,
            'tx_hash' => $validated['tx_hash'],
            'wallet_address' => $onChainAddress,
            'notarized_at' => Carbon::createFromTimestamp($onChainTimestamp),
        ]);
        return $note->fresh();
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
