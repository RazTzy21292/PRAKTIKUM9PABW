#!/usr/bin/env python3
"""
Installer kit SiPustaka (HMVC React).

Pemakaian (dari folder root project sipustaka_hmvc_react):
    python kit/install.py pre     # sebelum module:make  (patch composer.json)
    python kit/install.py post    # sesudah module:make  (patch + salin semua file)
"""
import json
import re
import shutil
import sys
from pathlib import Path

KIT = Path(__file__).resolve().parent
ROOT = Path.cwd()


def read(p):
    return Path(p).read_text(encoding='utf-8')


def write(p, s):
    Path(p).write_text(s, encoding='utf-8')


def note(ok, msg):
    print(('[OK]   ' if ok else '[MANUAL] ') + msg)


def patch_composer():
    p = ROOT / 'composer.json'
    data = json.loads(read(p))
    extra = data.setdefault('extra', {})
    extra.setdefault('merge-plugin', {})['include'] = ['Modules/*/composer.json']
    write(p, json.dumps(data, indent=4, ensure_ascii=False) + '\n')
    note(True, 'composer.json: merge-plugin ditambahkan (jalankan composer dump-autoload)')


def patch_app_tsx(path):
    p = ROOT / path
    if not p.exists():
        return
    s = read(p)
    if "name.includes('::')" in s:
        note(True, f'{path}: resolve sudah dipatch')
        return
    new = """resolve: (name) => {
            const pages = import.meta.glob([
                './pages/**/*.tsx',
                '../../Modules/*/resources/js/pages/**/*.tsx',
            ]);

            // Halaman modul ditulis dengan format NamaModul::nama-halaman
            if (name.includes('::')) {
                const [module, page] = name.split('::');

                return resolvePageComponent(
                    `../../Modules/${module}/resources/js/pages/${page}.tsx`,
                    pages,
                );
            }

            return resolvePageComponent(`./pages/${name}.tsx`, pages);
        },"""
    pat = re.compile(r"resolve:\s*\(name\)\s*=>\s*resolvePageComponent\(.*?import\.meta\.glob\([^)]*\),?\s*\),", re.S)
    s2, n = pat.subn(lambda m: new, s, count=1)
    if n:
        write(p, s2)
    note(bool(n), f'{path}: resolve halaman modul' + ('' if n else ' -> ganti manual sesuai Tahap A langkah 9'))


def patch_css():
    p = ROOT / 'resources/css/app.css'
    line = "@source '../../Modules/*/resources/js/**/*.{ts,tsx}';"
    s = read(p)
    if line not in s:
        s = s.rstrip('\n') + '\n\n' + line + '\n'
        write(p, s)
    note(True, 'app.css: @source modul ditambahkan')


def patch_tsconfig():
    p = ROOT / 'tsconfig.json'
    s = read(p)
    if 'Modules/*/resources/js' in s:
        note(True, 'tsconfig.json sudah dipatch')
        return
    s2, n = re.subn(r'("include"\s*:\s*\[)', r'\1\n        "Modules/*/resources/js/**/*.ts",\n        "Modules/*/resources/js/**/*.tsx",', s, count=1)
    if n:
        write(p, s2)
    note(bool(n), 'tsconfig.json: include folder Modules' + ('' if n else ' -> tambah manual'))


def patch_inertia_middleware():
    p = ROOT / 'app/Http/Middleware/HandleInertiaRequests.php'
    s = read(p)
    if "'flash'" in s:
        note(True, 'HandleInertiaRequests sudah memiliki flash')
        return
    flash = """...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],"""
    s2, n = re.subn(r'\.\.\.parent::share\(\$request\),', lambda m: flash, s, count=1)
    if n:
        write(p, s2)
    note(bool(n), 'HandleInertiaRequests: shared props flash' + ('' if n else ' -> tambah manual (Tahap B langkah 9)'))


def patch_bootstrap():
    p = ROOT / 'bootstrap/app.php'
    s = read(p)
    if "'role' =>" in s:
        note(True, 'bootstrap/app.php: alias role sudah ada')
        return
    alias = "\n        $middleware->alias(['role' => \\App\\Http\\Middleware\\EnsureUserHasRole::class]);\n"
    s2, n = re.subn(r'(->withMiddleware\(function \(Middleware \$middleware\)[^{]*\{)', lambda m: m.group(1) + alias, s, count=1)
    if n:
        write(p, s2)
    note(bool(n), 'bootstrap/app.php: alias middleware role' + ('' if n else ' -> tambah manual (Tahap C langkah 2)'))


def clean_modules():
    for m in ('Buku', 'Peminjaman', 'UserManagement'):
        base = ROOT / 'Modules' / m
        if not base.exists():
            note(False, f'Modules/{m} belum ada. Jalankan: php artisan module:make Buku Peminjaman UserManagement')
            continue
        for d in ('resources/views', 'resources/assets'):
            shutil.rmtree(base / d, ignore_errors=True)
        for f in ('vite.config.js', 'package.json'):
            (base / f).unlink(missing_ok=True)
        (base / 'resources/js/pages').mkdir(parents=True, exist_ok=True)
        # hapus file bawaan generator yang tidak dipakai
        for f in ('app/Http/Controllers/BukuController.php',):
            pass
    note(True, 'Modul dibersihkan (views, assets, vite.config.js, package.json dihapus)')


def copy_files():
    src = KIT / 'files'
    shutil.copytree(src, ROOT, dirs_exist_ok=True)
    note(True, 'Seluruh file kit disalin ke project')


def main():
    mode = sys.argv[1] if len(sys.argv) > 1 else ''
    if not (ROOT / 'artisan').exists():
        sys.exit('Jalankan skrip ini dari folder root project Laravel (yang berisi file artisan).')

    if mode == 'pre':
        patch_composer()
    elif mode == 'post':
        clean_modules()
        copy_files()
        patch_app_tsx('resources/js/app.tsx')
        patch_app_tsx('resources/js/ssr.tsx')
        patch_css()
        patch_tsconfig()
        patch_inertia_middleware()
        patch_bootstrap()
        print('\nSelesai. Lanjutkan: php artisan storage:link && php artisan migrate:fresh --seed')
    else:
        sys.exit(__doc__)


if __name__ == '__main__':
    main()
