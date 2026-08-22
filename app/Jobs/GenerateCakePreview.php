<?php

namespace App\Jobs;

use App\Ai\DemoCakePreviewGenerator;
use App\Contracts\CakePreviewGenerator;
use App\Models\CakeDesign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateCakePreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $designId)
    {
        $this->timeout = (int) config('services.gemini.image_timeout', 45) + 15;
    }

    public function handle(CakePreviewGenerator $generator): void
    {
        $design = CakeDesign::query()->findOrFail($this->designId);

        if (filled($design->preview_path)) {
            return;
        }

        $design->update([
            'preview_path' => $generator->generate($this->optionIds($design), $design->tiers),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $design = CakeDesign::query()->find($this->designId);

        if ($design === null || filled($design->preview_path)) {
            return;
        }

        $design->update([
            'preview_path' => app(DemoCakePreviewGenerator::class)->generate(
                $this->optionIds($design),
                $design->tiers,
            ),
        ]);
    }

    /**
     * @return list<int>
     */
    private function optionIds(CakeDesign $design): array
    {
        $optionIds = $design->selections['option_ids'] ?? [];

        if (! is_array($optionIds)) {
            return [];
        }

        return array_values(array_map('intval', $optionIds));
    }
}
