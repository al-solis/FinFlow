<?php
// app/Services/ChartOfAccountsService.php

namespace App\Services;

use App\Models\account_structure as AccountStructure;
use App\Models\account_structure_detail as AccountStructureDetail;
use App\Models\chart_of_account as ChartOfAccount;
use App\Models\chart_of_account_segment as ChartOfAccountSegment;
use App\Models\main_account as MainAccount;
use App\Models\segment as Segment;
use App\Models\segment_code as SegmentCode;
use App\Models\account_type as AccountType;
use App\Models\account_category as AccountCategory;
use App\Models\account_subcategory as AccountSubcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\SystemSettings;

class ChartOfAccountsService
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    /**
     * Generate or synchronize chart of accounts for a given account structure.
     *
     * @param AccountStructure $accountStructure
     * @param int $userId
     * @param bool $forceRegenerate
     * @return array
     */
    public function generateOrSync(AccountStructure $accountStructure, int $userId, bool $forceRegenerate = false): array
    {
        DB::beginTransaction();

        try {
            // Check if structure has details
            $details = $accountStructure->details()
                ->orderBy('sequence')
                ->get();

            if ($details->isEmpty()) {
                throw new \Exception('Account structure has no segments configured.');
            }

            // Validate structure has at least one main_account segment
            $hasMainAccount = $details->contains('source_type', 'main_account');
            if (!$hasMainAccount) {
                throw new \Exception('Account structure must include a GL Account (main_account) segment.');
            }

            // If force regenerate, delete existing chart of accounts
            if ($forceRegenerate) {
                $this->deleteChartOfAccounts($accountStructure);
            }

            // Get existing chart of accounts for sync
            $existingAccounts = ChartOfAccount::where('account_structure_id', $accountStructure->id)
                ->with('segments')
                ->get()
                ->keyBy(function ($coa) {
                    return $coa->account_code;
                });

            // Generate all possible combinations
            $combinations = $this->generateCombinations($details);

            if (empty($combinations)) {
                throw new \Exception('No account combinations generated. Please ensure segments have values.');
            }

            $stats = [
                'total' => count($combinations),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            // Process each combination
            foreach ($combinations as $combination) {
                try {
                    $result = $this->processCombination(
                        $accountStructure,
                        $details,
                        $combination,
                        $existingAccounts,
                        $userId
                    );

                    if ($result === 'created') {
                        $stats['created']++;
                    } elseif ($result === 'updated') {
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Failed to process combination', [
                        'combination' => $combination,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update account structure status
            if ($stats['created'] > 0 || $stats['updated'] > 0) {
                $accountStructure->update([
                    'status' => 1, // Generated
                    'last_synced_at' => now(),
                    'last_synced_by' => $userId,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'stats' => $stats,
                'message' => "Chart of accounts generation completed. Created: {$stats['created']}, Updated: {$stats['updated']}, Skipped: {$stats['skipped']}",
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Chart of accounts generation failed', [
                'account_structure_id' => $accountStructure->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate all combinations from the structure details.
     *
     * @param \Illuminate\Support\Collection $details
     * @return array
     */
    protected function generateCombinations($details): array
    {
        $combinations = [[]];

        foreach ($details as $detail) {
            $newCombinations = [];

            if ($detail->source_type === 'main_account') {
                // Get all main accounts
                $values = MainAccount::where('organization_id', $this->getOrganizationId())
                    ->where('status', 1)->get()->map(function ($account) {
                        return [
                            'value_id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->description,
                            'source_type' => 'main_account',
                            'account_type_id' => $account->account_type_id,
                            'account_category_id' => $account->account_category_id,
                            'account_subcategory_id' => $account->account_subcategory_id,
                        ];
                    })->toArray();
            } else {
                // Get segment codes for this segment
                $segment = Segment::find($detail->segment_id);
                if (!$segment) {
                    continue;
                }

                $values = SegmentCode::where('segment_id', $detail->segment_id)
                    ->where('status', 1)
                    ->get()
                    ->map(function ($code) use ($segment) {
                        // Pad the code to the segment length
                        $paddedCode = Str::padLeft($code->code, $segment->length, '0');
                        return [
                            'value_id' => $code->id,
                            'code' => $paddedCode,
                            'name' => $code->name,
                            'source_type' => 'segment',
                            'segment_id' => $segment->id,
                        ];
                    })->toArray();
            }

            // If no values found for this segment, skip
            if (empty($values)) {
                continue;
            }

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge($combination, [
                        'detail_' . $detail->id => $value
                    ]);
                }
            }

            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * Process a single combination.
     *
     * @param AccountStructure $accountStructure
     * @param \Illuminate\Support\Collection $details
     * @param array $combination
     * @param \Illuminate\Support\Collection $existingAccounts
     * @param int $userId
     * @return string
     */
    protected function processCombination(
        AccountStructure $accountStructure,
        $details,
        array $combination,
        $existingAccounts,
        int $userId
    ): string {
        // Build account code
        $codeParts = [];
        $segmentData = [];
        $mainAccountData = null;

        foreach ($details as $detail) {
            $key = 'detail_' . $detail->id;
            if (!isset($combination[$key])) {
                throw new \Exception("Missing value for detail {$detail->id}");
            }

            $value = $combination[$key];
            $codeParts[] = $value['code'];

            if ($detail->source_type === 'main_account') {
                $mainAccountData = $value;
            }

            $segmentData[] = [
                'detail_id' => $detail->id,
                'value_id' => $value['value_id'],
                'code' => $value['code'],
                'name' => $value['name'],
                'segment_id' => $detail->segment_id ?? null,
                'source_type' => $value['source_type'],
                'sequence' => $detail->sequence,
            ];
        }

        $accountCode = implode('', $codeParts);

        // Check if account already exists
        if ($existingAccounts->has($accountCode)) {
            $existingAccount = $existingAccounts->get($accountCode);

            // Check if we need to update (based on main account changes)
            $needUpdate = false;
            if ($mainAccountData) {
                if ($existingAccount->main_account_id != $mainAccountData['value_id']) {
                    $needUpdate = true;
                }
            }

            if (!$needUpdate) {
                return 'skipped';
            }

            // Update the account
            $this->updateAccount($existingAccount, $mainAccountData, $userId);
            return 'updated';
        }

        // Create new account
        $this->createAccount(
            $accountStructure,
            $accountCode,
            $segmentData,
            $mainAccountData,
            $userId
        );

        return 'created';
    }

    /**
     * Create a new chart of account.
     *
     * @param AccountStructure $accountStructure
     * @param string $accountCode
     * @param array $segmentData
     * @param array|null $mainAccountData
     * @param int $userId
     * @return ChartOfAccount
     */
    protected function createAccount(
        AccountStructure $accountStructure,
        string $accountCode,
        array $segmentData,
        ?array $mainAccountData,
        int $userId
    ): ChartOfAccount {
        // Extract main account details
        $mainAccount = MainAccount::find($mainAccountData['value_id']);
        if (!$mainAccount) {
            throw new \Exception("Main account not found");
        }

        // Get account name from the last segment or main account description
        $lastSegment = end($segmentData);
        $accountName = $lastSegment['name'];

        // If there are segments before the main account, append them
        $prefixes = [];
        foreach ($segmentData as $index => $seg) {
            if ($seg['source_type'] !== 'main_account') {
                $prefixes[] = $seg['name'];
            }
        }

        if (!empty($prefixes)) {
            $accountName = implode(' - ', $prefixes) . ' - ' . $mainAccount->description;
        }

        // Determine if this is a posting account
        // You might want to define rules for posting accounts
        $isPosting = true;

        // Some accounts like accumulated depreciation might not be posting
        if (strpos($mainAccount->description, 'Accumulated') !== false) {
            $isPosting = false;
        }

        // Create the chart of account
        $chartAccount = ChartOfAccount::create([
            'organization_id' => $this->getOrganizationId(),
            'account_structure_id' => $accountStructure->id,
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'main_account_id' => $mainAccount->id,
            'account_type_id' => $mainAccount->account_type_id,
            'account_category_id' => $mainAccount->account_category_id,
            'account_subcategory_id' => $mainAccount->account_subcategory_id,
            'is_posting' => $isPosting,
            'status' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Create segment associations
        foreach ($segmentData as $seg) {
            // Uncomment if you want to skip main_account segments from being stored in chart_of_account_segments           
            // if ($seg['source_type'] === 'main_account') {
            //     continue;                
            // }

            ChartOfAccountSegment::create([
                'chart_of_account_id' => $chartAccount->id,
                'account_structure_detail_id' => $seg['detail_id'],
                'segment_id' => $seg['source_type'] === 'main_account' ? null : $seg['segment_id'],
                'segment_value_id' => $seg['source_type'] === 'main_account' ? null : $seg['value_id'],
                'display_code' => $seg['code'],
                'display_name' => $seg['name'],
                'sequence' => $seg['sequence'],
            ]);
        }

        return $chartAccount;
    }

    /**
     * Update an existing chart of account.
     *
     * @param ChartOfAccount $chartAccount
     * @param array|null $mainAccountData
     * @param int $userId
     * @return ChartOfAccount
     */
    protected function updateAccount(
        ChartOfAccount $chartAccount,
        ?array $mainAccountData,
        int $userId
    ): ChartOfAccount {
        if ($mainAccountData) {
            $mainAccount = MainAccount::find($mainAccountData['value_id']);
            if ($mainAccount) {
                $chartAccount->update([
                    'main_account_id' => $mainAccount->id,
                    'account_type_id' => $mainAccount->account_type_id,
                    'account_category_id' => $mainAccount->account_category_id,
                    'account_subcategory_id' => $mainAccount->account_subcategory_id,
                    'updated_by' => $userId,
                ]);
            }
        }

        return $chartAccount;
    }

    /**
     * Delete all chart of accounts for a structure.
     *
     * @param AccountStructure $accountStructure
     * @return void
     */
    protected function deleteChartOfAccounts(AccountStructure $accountStructure): void
    {
        // Delete segments first
        ChartOfAccountSegment::whereIn(
            'chart_of_account_id',
            ChartOfAccount::where('account_structure_id', $accountStructure->id)->pluck('id')
        )->delete();

        // Delete chart of accounts
        ChartOfAccount::where('account_structure_id', $accountStructure->id)->delete();
    }

    /**
     * Validate if an account structure is ready for generation.
     *
     * @param AccountStructure $accountStructure
     * @return array
     */
    public function validateStructure(AccountStructure $accountStructure): array
    {
        $issues = [];
        $warnings = [];

        $details = $accountStructure->details()->orderBy('sequence')->get();

        if ($details->isEmpty()) {
            $issues[] = 'Account structure has no segments configured.';
        }

        $hasMainAccount = $details->contains('source_type', 'main_account');
        if (!$hasMainAccount) {
            $issues[] = 'Account structure must include a GL Account (main_account) segment.';
        }

        // Check each segment has values
        foreach ($details as $detail) {
            if ($detail->source_type === 'segment') {
                $segment = Segment::find($detail->segment_id);
                if (!$segment) {
                    $issues[] = "Segment with ID {$detail->segment_id} not found.";
                    continue;
                }

                $hasCodes = SegmentCode::where('segment_id', $segment->id)
                    ->where('status', 1)
                    ->exists();

                if (!$hasCodes) {
                    $warnings[] = "Segment '{$segment->description}' has no active codes.";
                }
            }
        }

        // Check if there are active main accounts
        $hasActiveAccounts = MainAccount::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)->exists();
        if (!$hasActiveAccounts) {
            $issues[] = 'No active main accounts found.';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get estimated number of accounts that would be generated.
     *
     * @param AccountStructure $accountStructure
     * @return int
     */
    public function estimateAccountCount(AccountStructure $accountStructure): int
    {
        $details = $accountStructure->details()->orderBy('sequence')->get();
        $count = 1;

        foreach ($details as $detail) {
            if ($detail->source_type === 'main_account') {
                $valuesCount = MainAccount::where('organization_id', $this->getOrganizationId())
                    ->where('status', 1)->count();
            } else {
                $segment = Segment::find($detail->segment_id);
                if ($segment) {
                    $valuesCount = SegmentCode::where('segment_id', $segment->id)
                        ->where('status', 1)
                        ->count();
                } else {
                    $valuesCount = 0;
                }
            }

            if ($valuesCount === 0) {
                return 0;
            }
            $count *= $valuesCount;
        }

        return $count;
    }
}