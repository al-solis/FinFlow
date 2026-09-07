<?php

namespace App\Traits;

use App\Services\PiiMaskingService;
use Illuminate\Support\Facades\Auth;

trait MasksPii
{
    protected ?PiiMaskingService $piiMasker = null;

    /**
     * Get PII masking service
     */
    protected function getPiiMasker(): PiiMaskingService
    {
        if (!$this->piiMasker) {
            $this->piiMasker = new PiiMaskingService(Auth::user());
        }
        return $this->piiMasker;
    }

    /**
     * Mask a single value
     */
    protected function maskPii($value, string $piiType): string
    {
        return $this->getPiiMasker()->mask($value, $piiType);
    }

    /**
     * Mask a model's PII fields
     */
    protected function maskModelPii($model, array $fieldsWithTypes)
    {
        if (!$model) {
            return $model;
        }

        $masker = $this->getPiiMasker();

        if ($masker->hasFullAccess()) {
            return $model;
        }

        foreach ($fieldsWithTypes as $field => $piiType) {
            if (isset($model->{$field})) {
                $model->{$field} = $masker->mask($model->{$field}, $piiType);
            }
        }

        return $model;
    }

    /**
     * Mask a collection of models
     */
    protected function maskCollectionPii($collection, array $fieldsWithTypes)
    {
        if (!$collection || $collection->isEmpty()) {
            return $collection;
        }

        $masker = $this->getPiiMasker();

        if ($masker->hasFullAccess()) {
            return $collection;
        }

        return $collection->map(function ($item) use ($fieldsWithTypes, $masker) {
            foreach ($fieldsWithTypes as $field => $piiType) {
                if (isset($item->{$field})) {
                    $item->{$field} = $masker->mask($item->{$field}, $piiType);
                }
            }
            return $item;
        });
    }

    /**
     * Check if user has full PII access
     */
    protected function hasFullPiiAccess(): bool
    {
        return $this->getPiiMasker()->hasFullAccess();
    }

    /**
     * Check if PII masking is active
     */
    protected function isPiiMaskingActive(): bool
    {
        return !$this->getPiiMasker()->hasFullAccess();
    }
}