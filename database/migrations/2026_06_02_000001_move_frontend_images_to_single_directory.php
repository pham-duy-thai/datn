<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep persisted frontend image paths aligned with public/images/frontend.
     */
    public function up(): void
    {
        $this->updateImagePaths(
            fn (string $path): string => str_starts_with($path, 'images/frontend/')
                ? $path
                : 'images/frontend/'.substr($path, strlen('images/'))
        );
    }

    public function down(): void
    {
        $this->updateImagePaths(
            fn (string $path): string => str_starts_with($path, 'images/frontend/')
                ? 'images/'.substr($path, strlen('images/frontend/'))
                : $path,
            'images/frontend/%'
        );
    }

    private function updateImagePaths(callable $transform, string $pattern = 'images/%'): void
    {
        foreach ($this->imageColumns() as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, 'like', $pattern)
                ->get(['id', $column])
                ->each(function (object $record) use ($table, $column, $transform): void {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$column => $transform($record->{$column})]);
                });
        }
    }

    private function imageColumns(): array
    {
        return [
            ['departments', 'image'],
            ['doctors', 'avatar'],
            ['services', 'image'],
            ['news', 'thumbnail'],
            ['banners', 'image'],
        ];
    }
};
