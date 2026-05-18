<?php

namespace App\Entity\Forum\Traits;

trait ForumTextRepairTrait
{
    protected function repairForumText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!preg_match('/(?:├|Ã|ÔÇ)/u', $value)) {
            return $value;
        }

        $repaired = @iconv('UTF-8', 'CP850//TRANSLIT', $value);

        return is_string($repaired) && $repaired !== '' ? $repaired : $value;
    }
}
