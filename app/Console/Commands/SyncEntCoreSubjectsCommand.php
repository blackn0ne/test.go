<?php

namespace App\Console\Commands;

use App\Support\CoreSubjects;
use Database\Seeders\EntSystemSeeder;
use Illuminate\Console\Command;

class SyncEntCoreSubjectsCommand extends Command
{
    protected $signature = 'ent:sync {--blueprint : Также пересоздать шаблон ЕНТ}';

    protected $description = 'Пометить существующие обязательные предметы и удалить пустые дубликаты от сидера';

    public function handle(): int
    {
        $this->info('Синхронизация обязательных предметов...');

        foreach (CoreSubjects::resolveAll() as $subject) {
            $this->line("  ✓ {$subject->name} (id: {$subject->id}, code: {$subject->code})");
        }

        if ($this->option('blueprint')) {
            $this->info('Пересоздание шаблона ЕНТ...');
            $this->callSilent('db:seed', ['--class' => EntSystemSeeder::class]);
            $this->line('  ✓ Шаблон ent_standard готов');
        }

        $this->newLine();
        $this->info('Готово. Проверьте справочник предметов — дубликаты без вопросов удалены.');

        return self::SUCCESS;
    }
}
