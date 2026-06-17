<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiResult;
use App\Models\KpiTarget;
use App\Repositories\Contracts\ResultRepositoryInterface;
use App\Services\KpiEvaluator;
use Illuminate\Database\Eloquent\Model;

class ResultRepository extends BaseRepository implements ResultRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new KpiResult();
    }

    public function record(KpiTarget $target, array $data, int $recordedBy): KpiResult
    {
        $resultValue = $data['result_value'] ?? null;
        $resultText = $data['result_text'] ?? null;

        $status = KpiEvaluator::evaluate(
            $target->operator,
            $target->target_value !== null ? (float) $target->target_value : null,
            $resultValue !== null ? (float) $resultValue : null,
            $resultText
        );

        return KpiResult::updateOrCreate(
            ['target_id' => $target->id],
            [
                'indicator_id' => $target->indicator_id,
                'result_value' => $resultValue,
                'result_text' => $resultText,
                'pass_status' => $status,
                'note' => $data['note'] ?? null,
                'recorded_by' => $recordedBy,
                'recorded_at' => now(),
            ]
        );
    }
}
