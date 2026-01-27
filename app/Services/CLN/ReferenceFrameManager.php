<?php

namespace App\Services\CLN;

use App\Models\CLN\ReferenceFrame;

class ReferenceFrameManager
{
    public array $frameStack = [];

    public function enterEmbedding(string $constructionType): ReferenceFrame
    {
        $newFrame = new ReferenceFrame(
            id: uniqid(),
            constructionType: $constructionType,
            position: 0,
            parent: $this->currentFrame()
        );
        $this->frameStack[] = $newFrame;
        return $newFrame;
    }

    public function exitEmbedding(): ?ReferenceFrame
    {
        if (count($this->frameStack) > 1) {
            array_pop($this->frameStack);
            return $this->currentFrame();
        }
        return null;
    }

    public function currentFrame(): ?ReferenceFrame
    {
        return end($this->frameStack) ?: null;
    }

    public function advancePosition(): void
    {
        if ($frame = $this->currentFrame()) {
            $frame->position++;
        }
    }
}
