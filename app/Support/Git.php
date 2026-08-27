<?php

namespace App\Support;

class Git
{
    /**
     * Get current git branch
     */
    public static function currentBranch(): string
    {
        $branch = config('app.branch');

        if (! empty($branch)) {
            return $branch;
        }

        $gitHead = base_path('.git/HEAD');

        if (! file_exists($gitHead)) {
            return 'unknown';
        }

        $head = file_get_contents($gitHead);

        if ($head === false) {
            return 'unknown';
        }

        return trim(str_replace('ref: refs/heads/', '', $head));
    }
}
