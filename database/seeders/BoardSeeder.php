<?php

namespace Database\Seeders;

use App\Models\Board;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    /**
     * Seed one sample board with three columns and a few tasks.
     * Safe to run repeatedly: it reuses the board by title.
     */
    public function run(): void
    {
        $board = Board::firstOrCreate(
            ['title' => 'Website Relaunch'],
            ['description' => 'Sample board for local development.']
        );

        $data = [
            'To Do' => ['Write homepage copy', 'Choose colour palette', 'Set up analytics'],
            'In Progress' => ['Build navigation component', 'Design contact form'],
            'Done' => ['Register domain', 'Create repository'],
        ];

        $columnPosition = 0;
        foreach ($data as $columnTitle => $tasks) {
            $column = $board->columns()->firstOrCreate(
                ['title' => $columnTitle],
                ['position' => $columnPosition]
            );
            $columnPosition++;

            foreach ($tasks as $i => $taskTitle) {
                $column->tasks()->firstOrCreate(
                    ['title' => $taskTitle],
                    ['position' => $i]
                );
            }
        }
    }
}
