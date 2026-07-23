<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\segment;
use App\Models\segment_code;

class SegmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $totalSegments = segment::count();
        $activeSegments = segment::where('status', '1')->count();
        $inactiveSegments = segment::where('status', '0')->count();

        $query = segment::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('length', 'like', "%{$search}%");
            });
        }

        if ($searchstatus != null) {
            $query->where('status', $searchstatus);
        }

        $segments = $query->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchstatus' => $searchstatus,
            ]);

        return view('setup.chart.segment.index', compact('totalSegments', 'activeSegments', 'inactiveSegments', 'segments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:segments,code',
            'description' => 'required',
            'length' => 'required|integer|min:1|max:10',
            'status' => 'required|integer'
        ]);

        segment::create([
            'code' => $request->code,
            'description' => $request->description,
            'length' => $request->length,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('setup.chart.segment.index')->with('success', 'Segment created successfully.');
    }

    public function update(Request $request, $id)
    {
        $segment = segment::findOrFail($id);

        $request->validate([
            'edit_code' => 'required|unique:segments,code,' . $segment->id,
            'edit_description' => 'required',
            'edit_length' => 'required|integer|min:1|max:10',
            'edit_status' => 'required|integer'
        ]);

        $withSegmentCode = segment_code::where('segment_id', $segment->id)
            ->where('status', 1)
            ->exists();

        if ($withSegmentCode && $request->edit_length != $segment->length) {
            //session error message
            // return redirect()->route('setup.chart.segment.index')->with('error', 'Cannot change the length of a segment that has active segment codes.');
            //redirect back to modal with error message
            return back()
                ->withInput()
                ->with('error', 'Cannot change the length of a segment that has active segment codes.');
        }

        if ($withSegmentCode && $request->edit_status != $segment->status) {
            //session error message
            // return redirect()->route('setup.chart.segment.index')->with('error', 'Cannot change the status of a segment that has active segment codes.');
            return back()
                ->withInput()
                ->with('error', 'Cannot change the status of a segment that has active segment codes.');
        }

        $segment->update([
            'code' => $request->edit_code,
            'description' => $request->edit_description,
            'length' => $request->edit_length,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('setup.chart.segment.index')->with('success', 'Segment updated successfully.');
    }
}
