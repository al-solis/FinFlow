<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SystemSettings;
use App\Models\account_structure;
use App\Models\account_structure_detail;
use App\Models\segment;

class AccountStructureDetailController extends Controller
{
    public function index($accountStructureId)
    {
        $settings = SystemSettings::get();
        $accountStructure = account_structure::findOrFail($accountStructureId);

        $glExists = account_structure_detail::where('account_structure_id', $accountStructure->id)
            ->where('source_type', 'main_account')
            ->exists();

        if (!$glExists) {
            account_structure_detail::create([
                'account_structure_id' => $accountStructure->id,
                'source_type' => 'main_account',
                'segment_id' => null,
                'sequence' => 1,
                'separator' => '-',
            ]);
        }

        $details = account_structure_detail::with('segment')
            ->where('account_structure_id', $accountStructure->id)
            ->orderBy('sequence')
            ->get();

        $usedSegmentIds = $details->pluck('segment_id')->filter();

        $availableSegments = segment::whereNotIn('id', $usedSegmentIds)
            ->where('status', 1)
            ->get();

        return view('gl.chart.account_structure_details.index', compact('accountStructure', 'details', 'availableSegments', 'settings'));
    }

    public function store(Request $request, $accountStructureId)
    {
        $accountStructure = account_structure::findOrFail($accountStructureId);

        $request->validate([
            'segment_id' => 'required|exists:segments,id',
            'separator' => 'nullable|max:2',
        ]);

        $alreadyAdded = account_structure_detail::where('account_structure_id', $accountStructure->id)
            ->where('segment_id', $request->segment_id)
            ->exists();

        if ($alreadyAdded) {
            return back()->with('error', 'This segment has already been added.');
        }

        $nextSequence = (int) account_structure_detail::where('account_structure_id', $accountStructure->id)->max('sequence') + 1;

        account_structure_detail::create([
            'account_structure_id' => $accountStructure->id,
            'source_type' => 'segment',
            'segment_id' => $request->segment_id,
            'sequence' => $nextSequence,
            'separator' => $request->separator ?: '-',
        ]);

        return redirect()->route('gl.chart.account_structure_details.index', $accountStructure->id)
            ->with('success', 'Segment added to structure.');
    }

    public function moveUp($accountStructureId, $detailId)
    {
        // dd($accountStructureId, $detailId);
        $this->swapSequence($accountStructureId, $detailId, -1);
        return back();
    }

    public function moveDown($accountStructureId, $detailId)
    {
        // dd($accountStructureId, $detailId);
        $this->swapSequence($accountStructureId, $detailId, 1);
        return back();
    }

    private function swapSequence($accountStructureId, $detailId, int $direction)
    {
        $current = account_structure_detail::where('account_structure_id', $accountStructureId)->findOrFail($detailId);

        $target = account_structure_detail::where('account_structure_id', $accountStructureId)
            ->where('sequence', $current->sequence + $direction)
            ->first();

        if (!$target) {
            return;
        }

        DB::transaction(function () use ($current, $target) {
            $currentSeq = $current->sequence;
            $targetSeq = $target->sequence;

            $current->update(['sequence' => 0]);
            $target->update(['sequence' => $currentSeq]);
            $current->update(['sequence' => $targetSeq]);
        });
    }
    public function destroy($accountStructureId, $detailId)
    {
        $detail = account_structure_detail::where('account_structure_id', $accountStructureId)->findOrFail($detailId);

        if ($detail->source_type === 'main_account') {
            return back()->with('error', 'The GL Account segment cannot be removed.');
        }

        DB::transaction(function () use ($accountStructureId, $detail) {
            $detail->delete();

            // Close the gap left in `sequence` so move-up/move-down (which look
            // for an adjacent sequence value) keep working after a deletion.
            $remaining = account_structure_detail::where('account_structure_id', $accountStructureId)
                ->orderBy('sequence')
                ->get();

            foreach ($remaining as $i => $row) {
                $newSequence = $i + 1;
                if ($row->sequence !== $newSequence) {
                    $row->update(['sequence' => $newSequence]);
                }
            }
        });

        return redirect()->route('gl.chart.account_structure_details.index', $accountStructureId)
            ->with('success', 'Segment removed from structure.');
    }
}