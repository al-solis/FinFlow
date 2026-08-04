<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\segment;
use App\Models\segment_code;

class SegmentCodeController extends Controller
{
    public function index(Request $request)
    {
        $segmentId = $request->route('segmentId');
        $segment = segment::findOrFail($segmentId);
        $segmentCodes = segment_code::where('segment_id', $segmentId)->paginate(env('APP_PAGINATE_PER_PAGE', 10));

        return view('gl.chart.segment.segment_account.index', compact('segment', 'segmentCodes'));
    }

    public function store(Request $request, $segmentId)
    {
        $request->validate([
            'code' => [
                'required',
                Rule::unique('segment_codes', 'code')
                    ->where(fn($query) => $query->where('segment_id', $segmentId)),
            ],
            'name' => 'required|max:60',
            'description' => 'required|max:120',
            'status' => 'required|integer'
        ]);

        segment_code::create([
            'segment_id' => $segmentId,
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('gl.segments.segment_account.index', ['segmentId' => $segmentId])
            ->with('success', 'Segment code created successfully.');
    }

    public function update(Request $request, $id)
    {
        $segmentCode = segment_code::findOrFail($id);
        $segment = segment::findOrFail($segmentCode->segment_id);

        $request->validate([
            // 'edit_code' => 'required|min:' . $segment->length . '|max:' . $segment->length . '|unique:segment_codes,code,' . $id,
            'edit_code' => [
                'required',
                'size:' . $segment->length,
                Rule::unique('segment_codes', 'code')
                    ->where(fn($query) => $query->where('segment_id', $segmentCode->segment_id))
                    ->ignore($segmentCode->id),
            ],
            'edit_name' => 'required|max:60',
            'edit_description' => 'required|max:120',
            'edit_status' => 'required|integer'
        ]);

        $segmentCode->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('gl.segments.segment_account.index', ['segmentId' => $segmentCode->segment_id])
            ->with('success', 'Segment code updated successfully.');
    }

}
