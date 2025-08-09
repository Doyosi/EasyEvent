<?php

namespace Doyosi\EasyEvent\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'doyosi:event
        {--install : Publish config & migrations and run migrate}
        {--vite : Patch vite.config.js and scaffold JS files}
        {--run-npm= : Optionally run an npm command, e.g. "dev" or "build"}
        {--uninstall : Remove Vite wiring, scaffolds, and optionally app artifacts}
        {--purge : With --uninstall, also delete published config and migrations}
        {--drop-table : With --uninstall, drop the easy_events table}
        {--restore-vite= : Rewrite vite.config.js input from manifest (default: doyosi.vite.json)}
        {--backup : Create a .bak copy of vite.config.js before modifications}
        {--force : Do not ask for confirmation on destructive actions}';

    protected $description = 'Install / uninstall Doyosi EasyEvent and manage Vite wiring.';

    /**
     * Tracks if we already created a vite.config.js backup this run.
     */
    protected bool $didViteBackup = false;

    public function handle(): int
    {
        // guard: mutually exclusive
        if ($this->option('install') && $this->option('uninstall')) {
            $this->error('Choose either --install or --uninstall.');
            return self::INVALID;
        }

        // UNINSTALL
        if ($this->option('uninstall')) {
            return $this->uninstall();
        }

        // INSTALL
        if ($this->option('install')) {
            $this->doInstall();
        }

        if ($this->option('vite')) {
            $this->wireVite();
        }

        // RESTORE VITE INPUTS FROM MANIFEST
        $restoreOpt = $this->option('restore-vite');
        if ($restoreOpt !== null) {
            $path = $restoreOpt ?: base_path('doyosi.vite.json'); // default if passed without value
            $this->restoreViteFromManifest($path);
        }

        if ($cmd = $this->option('run-npm')) {
            $this->runNpm($cmd);
        }

        $this->newLine();
        $this->info('EasyEvent done.');
        return self::SUCCESS;
    }

    /* =========================
     * INSTALL
     * ========================= */

    protected function doInstall(): void
    {
        $this->callSilent('vendor:publish', [
            '--provider' => "Doyosi\\EasyEvent\\EasyEventServiceProvider",
            '--tag' => 'easy-event-config',
            '--force' => true,
        ]);
        $this->info('✓ Config published');

        $this->callSilent('vendor:publish', [
            '--provider' => "Doyosi\\EasyEvent\\EasyEventServiceProvider",
            '--tag' => 'easy-event-migrations',
            '--force' => false,
        ]);
        $this->info('✓ Migrations published');

        $this->call('migrate');
        $this->info('✓ Database migrated');
    }

    protected function wireVite(): void
    {
        $this->scaffoldJs();
        $this->patchViteConfig();
        $this->info('✓ Vite wired (JS scaffolded + vite.config.js patched if possible)');
        $this->line('   Next: run "npm run dev" (or pass --run-npm=dev).');
    }

    protected function scaffoldJs(): void
    {
        $entry = base_path('resources/js/easy-event.js');
        $moduleDir = base_path('resources/js/modules');
        $module = $moduleDir . '/EasyEventWidget.js';

        if (! is_dir(dirname($entry))) @mkdir(dirname($entry), 0777, true);
        if (! is_dir($moduleDir)) @mkdir($moduleDir, 0777, true);

        if (! file_exists($module)) {
            file_put_contents($module, $this->easyEventWidgetJs());
            $this->info('  ├─ Created: resources/js/modules/EasyEventWidget.js');
        } else {
            $this->line('  ├─ Skipped (exists): resources/js/modules/EasyEventWidget.js');
        }

        if (! file_exists($entry)) {
            file_put_contents($entry, $this->easyEventEntryJs());
            $this->info('  └─ Created: resources/js/easy-event.js');
        } else {
            $this->line('  └─ Skipped (exists): resources/js/easy-event.js');
        }
    }

    protected function patchViteConfig(): void
    {
        $vite = base_path('vite.config.js');
        if (! file_exists($vite)) {
            $this->warn("! vite.config.js not found. Skipping Vite patch.");
            $this->line('  Add this entry manually to @vite: resources/js/easy-event.js');
            return;
        }

        $content = file_get_contents($vite);
        $inputPath = 'resources/js/easy-event.js';

        if (strpos($content, $inputPath) !== false) {
            $this->line('  Vite entry already present: resources/js/easy-event.js');
            return;
        }

        $patched = preg_replace_callback(
            '/laravel\(\s*\{\s*([^}]*?)input\s*:\s*\[([^\]]*)\]/si',
            function ($m) use ($inputPath) {
                $inside = rtrim($m[2]);
                $sep = trim($inside) === '' ? '' : ",\n        ";
                $newInside = $inside . $sep . "'" . $inputPath . "'";
                return str_replace($m[2], $newInside, $m[0]);
            },
            $content,
            1,
            $count
        );

        if ($count === 0) {
            $this->warn('! Could not locate laravel({ input: [...] }) in vite.config.js.');
            $this->line("  Add this to your laravel-vite-plugin input array:");
            $this->line("    'resources/js/easy-event.js',");
            return;
        }

        $this->backupViteConfig();
        file_put_contents($vite, $patched);
        $this->info('  ✓ Patched vite.config.js (added resources/js/easy-event.js to input)');
    }

    protected function runNpm(string $cmd): void
    {
        $this->line("» Running: npm run {$cmd}");
        $process = Process::fromShellCommandline('npm run ' . escapeshellarg($cmd), base_path());
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) { echo $buffer; });

        if (! $process->isSuccessful()) {
            $this->warn('! npm run failed (see output).');
        }
    }

    /* =========================
     * UNINSTALL
     * ========================= */

    protected function uninstall(): int
    {
        $warn = [];
        if ($this->option('purge')) $warn[] = 'delete published config & migrations';
        if ($this->option('drop-table')) $warn[] = 'drop the easy_events table';

        $headline = 'This will remove Vite wiring and scaffolded JS';
        if ($warn) $headline .= ' AND will ' . implode(' AND ', $warn);

        if (! $this->option('force') && ! $this->confirm($headline . '. Proceed?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->unpatchViteConfig();
        $this->removeScaffoldJs();

        if ($this->option('purge')) {
            $this->removeConfigFile();
            $this->removeMigrations();
        }

        if ($this->option('drop-table')) {
            $this->dropTable();
        }

        if ($cmd = $this->option('run-npm')) {
            $this->runNpm($cmd);
        }

        $this->info('✓ EasyEvent uninstall complete.');
        return self::SUCCESS;
    }

    protected function unpatchViteConfig(): void
    {
        $vite = base_path('vite.config.js');
        if (! file_exists($vite)) {
            $this->line('  (vite.config.js not found; skipping unpatch)');
            return;
        }

        $content = file_get_contents($vite);
        $before = $content;

        $entry = preg_quote("'resources/js/easy-event.js'", '/');
        $content = preg_replace('/,\s*' . $entry . '\s*/', '', $content);
        $content = preg_replace('/' . $entry . '\s*,\s*/', '', $content);
        $content = preg_replace('/' . $entry . '\s*/', '', $content);
        $content = preg_replace('/,\s*\]/', ']', $content);

        if ($before !== $content) {
            $this->backupViteConfig();
            file_put_contents($vite, $content);
            $this->info('  ✓ Removed resources/js/easy-event.js from vite.config.js');
        } else {
            $this->line('  (vite entry not present; nothing to unpatch)');
        }
    }

    protected function removeScaffoldJs(): void
    {
        $files = [
            base_path('resources/js/easy-event.js') => $this->easyEventEntryMarker(),
            base_path('resources/js/modules/EasyEventWidget.js') => $this->easyEventWidgetMarker(),
        ];

        foreach ($files as $file => $marker) {
            if (! file_exists($file)) {
                $this->line("  (skip) {$this->rel($file)} not found");
                continue;
            }

            $content = file_get_contents($file);
            if (strpos($content, $marker) !== false) {
                @unlink($file);
                $this->info("  ✓ Deleted {$this->rel($file)}");
            } else {
                // Keep user-modified files; move aside to .bak
                $bak = $file . '.bak';
                @rename($file, $bak);
                $this->warn("  • Detected custom changes. Moved to: {$this->rel($bak)}");
            }
        }
    }

    protected function removeConfigFile(): void
    {
        $path = config_path('easy-event.php');
        if (file_exists($path)) {
            @unlink($path);
            $this->info('  ✓ Deleted config/easy-event.php');
        } else {
            $this->line('  (config/easy-event.php not found)');
        }
    }

    protected function removeMigrations(): void
    {
        $dir = database_path('migrations');
        if (! is_dir($dir)) {
            $this->line('  (database/migrations not found)');
            return;
        }

        $deleted = 0;
        foreach (glob($dir . '/*create_easy_events_table.php') as $file) {
            @unlink($file);
            $deleted++;
        }

        if ($deleted > 0) {
            $this->info("  ✓ Deleted {$deleted} migration file(s) for easy_events");
        } else {
            $this->line('  (no easy_events migration files found)');
        }
    }

    protected function dropTable(): void
    {
        $table = config('easy-event.table', 'easy_events');
        if (Schema::hasTable($table)) {
            Schema::drop($table);
            $this->info("  ✓ Dropped table {$table}");
        } else {
            $this->line("  (table {$table} not found)");
        }
    }

    /* =========================
     * VITE RESTORE FROM MANIFEST
     * ========================= */

    protected function restoreViteFromManifest(string $manifestPath): void
    {
        $vite = base_path('vite.config.js');
        if (! file_exists($vite)) {
            $this->error('vite.config.js not found.');
            return;
        }

        $content = file_get_contents($vite);

        // If manifest missing → bootstrap one from current inputs and stop.
        if (! file_exists($manifestPath)) {
            $current = $this->extractViteInputs($content);
            $this->writeViteManifest($manifestPath, $current);
            $this->warn("Manifest not found. Created template at: " . $this->rel($manifestPath));
            $this->line('Edit the "input" array then re-run: php artisan doyosi:event --restore-vite');
            return;
        }

        $data = json_decode(file_get_contents($manifestPath), true);
        if (! is_array($data) || ! isset($data['input']) || ! is_array($data['input'])) {
            $this->error('Invalid manifest. Expect JSON: { "input": ["..."] }');
            return;
        }

        $list = array_values(array_unique(array_filter(array_map('strval', $data['input']))));
        if (empty($list)) {
            $this->error('Manifest "input" is empty.');
            return;
        }

        // Build pretty array and replace first input: [...] occurrence.
        $quoted = array_map(fn($p) => "'".$p."'", $list);
        $pretty = "\n        " . implode(",\n        ", $quoted) . "\n      ";

        $patched = preg_replace(
            '/input\s*:\s*\[[^\]]*\]/si',
            "input: [{$pretty}]",
            $content,
            1,
            $count
        );

        if ($count === 0) {
            $this->error('Could not find input: [...] in vite.config.js');
            return;
        }

        $this->backupViteConfig();
        file_put_contents($vite, $patched);
        $this->info('✓ Rewrote vite.config.js inputs from manifest: ' . $this->rel($manifestPath));
    }

    protected function extractViteInputs(string $viteContent): array
    {
        if (! preg_match('/input\s*:\s*\[([^\]]*)\]/si', $viteContent, $m)) return [];
        $inside = $m[1];
        if (! preg_match_all('/([\'"])(.*?)\1/', $inside, $mm)) return [];
        return array_values(array_unique($mm[2] ?? []));
    }

    protected function writeViteManifest(string $path, array $inputs): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) @mkdir($dir, 0777, true);
        $json = json_encode(['input' => array_values($inputs)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $json . PHP_EOL);
    }

    /* =========================
     * BACKUP
     * ========================= */

    /**
     * Backup vite.config.js once per run when --backup is passed.
     * Creates vite.config.js.YYYYmmdd-HHMMSS.bak in project root.
     */
    protected function backupViteConfig(): void
    {
        if ($this->didViteBackup || ! $this->option('backup')) return;

        $vite = base_path('vite.config.js');
        if (! file_exists($vite)) {
            $this->line('  (vite.config.js not found; nothing to backup)');
            return;
        }

        $ts = date('Ymd-His');
        $dest = base_path("vite.config.js.{$ts}.bak");

        if (@copy($vite, $dest)) {
            $this->info('  ✓ Backed up vite.config.js -> ' . $this->rel($dest));
            $this->didViteBackup = true;
        } else {
            $this->warn('  ! Failed to backup vite.config.js');
        }
    }

    /* =========================
     * JS STUBS + MARKERS
     * ========================= */

    protected function easyEventEntryMarker(): string { return '//@DOYOSI_EASY_EVENT_STUB_ENTRY'; }
    protected function easyEventWidgetMarker(): string { return '//@DOYOSI_EASY_EVENT_STUB_WIDGET'; }

    protected function easyEventEntryJs(): string
    {
        $marker = $this->easyEventEntryMarker();
        return <<<JS
{$marker}
/**
 * EasyEvent front-end entry
 * Load with: @vite('resources/js/easy-event.js')
 */
import { EasyEventWidget } from './modules/EasyEventWidget'

document.addEventListener('DOMContentLoaded', () => {
  const els = document.querySelectorAll('[data-easy-event]')
  els.forEach(el => {
    const limit = Number(el.getAttribute('data-limit') || 5)
    const endpoint = el.getAttribute('data-endpoint') || '/api/easy-events'
    const w = new EasyEventWidget({ target: el, endpoint, limit })
    w.init()
  })
})

window.DoyosiEasyEventWidget = EasyEventWidget
JS;
    }

    protected function easyEventWidgetJs(): string
    {
        $marker = $this->easyEventWidgetMarker();
        return <<<JS
{$marker}
/**
 * EasyEventWidget
 * Render a small event list.
 *
 * @param {Object} options
 * @param {string|HTMLElement} options.target
 * @param {string} [options.endpoint='/api/easy-events']
 * @param {number} [options.limit=5]
 *
 * @example
 * import { EasyEventWidget } from '@/modules/EasyEventWidget'
 * const widget = new EasyEventWidget({ target: '#eventsBox', limit: 10 })
 * widget.init()
 */
export class EasyEventWidget {
  constructor({ target, endpoint = '/api/easy-events', limit = 5 } = {}) {
    this.el = typeof target === 'string' ? document.querySelector(target) : target
    this.endpoint = endpoint
    this.limit = limit
  }

  async init() {
    if (!this.el) return
    const events = await this.fetchEvents()
    this.render(events.slice(0, this.limit))
  }

  async fetchEvents() {
    try {
      const res = await fetch(this.endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      if (!res.ok) throw new Error('Failed to load events')
      return await res.json()
    } catch (e) {
      console.error('[EasyEventWidget]', e)
      return []
    }
  }

  render(items) {
    this.el.innerHTML = items.length
      ? \`<ul class="space-y-2">\${items.map(i => \`
           <li class="p-3 rounded border">
             <div class="font-semibold">\${i.title}</div>
             <div class="text-sm opacity-70">\${i.starts_at ?? ''}</div>
           </li>
         \`).join('')}</ul>\`
      : '<div class="p-3 text-sm opacity-70">No events.</div>'
  }
}
JS;
    }

    protected function rel(string $abs): string
    {
        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $abs);
    }
}
