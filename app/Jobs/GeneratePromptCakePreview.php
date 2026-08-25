<?php

namespace App\Jobs;

use App\Ai\DemoPromptCakeImageGenerator;
use App\Contracts\PromptCakeImageGenerator;
use App\Models\CakeDesign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GeneratePromptCakePreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $designId)
    {
        $this->timeout = (int) config('services.gemini.image_timeout', 45) + 15;
    }

    public function handle(PromptCakeImageGenerator $generator): void
    {
        $design = CakeDesign::query()->findOrFail($this->designId);

        if (filled($design->preview_path)) {
            return;
        }

        $design->update([
            'preview_path' => $generator->generate($this->context($design)),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $design = CakeDesign::query()->find($this->designId);

        if ($design === null || filled($design->preview_path)) {
            return;
        }

        $design->update([
            'preview_path' => app(DemoPromptCakeImageGenerator::class)->generate($this->context($design)),
        ]);
    }

    /**
     * @return array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}
     */
    private function context(CakeDesign $design): array
    {
        $selections = $design->selections ?? [];

        return [
            'prompt' => (string) ($selections['prompt'] ?? ''),
            'reference_path' => $selections['reference_path'] ?? null,
            'cake_name' => $selections['cake_name'] ?? null,
            'cake_description' => $selections['cake_description'] ?? null,
        ];
    }
}
