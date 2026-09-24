import { createServer } from 'node:http';
import { spawn, execFileSync } from 'node:child_process';
import { readFile, mkdtemp, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const browser = process.env.BROWSER_BINARY || [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
].find(existsSync);
if (!browser) throw new Error('Set BROWSER_BINARY to Chrome or Chromium');
const html = execFileSync(process.env.PHP_BINARY || 'php', ['tests/browser-fixture.php'], { cwd: root, encoding: 'utf8' });
const server = createServer(async (request, response) => {
    const pathname = new URL(request.url, 'http://localhost').pathname;
    response.setHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'");
    if (pathname === '/') { response.setHeader('Content-Type', 'text/html; charset=UTF-8'); response.end(html); return; }
    if (!/^\/(public\/assets\/|tests\/browser-tests\.js$)/.test(pathname) || pathname.includes('..')) {
        response.writeHead(404).end(); return;
    }
    try {
        response.setHeader('Content-Type', pathname.endsWith('.js') ? 'text/javascript; charset=UTF-8' : pathname.endsWith('.css') ? 'text/css' : 'application/octet-stream');
        response.end(await readFile(path.join(root, pathname)));
    } catch { response.writeHead(404).end(); }
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const profile = await mkdtemp(path.join(tmpdir(), 'travel-compass-browser-'));
let child;
try {
    const url = `http://127.0.0.1:${server.address().port}/`;
    child = spawn(browser, ['--headless=new', '--no-sandbox', '--disable-gpu', '--disable-background-networking',
        `--user-data-dir=${profile}`, `--window-size=${process.env.BROWSER_WINDOW_SIZE || '1280,900'}`, '--dump-dom', '--virtual-time-budget=10000', url], { windowsHide: true });
    let output = '';
    let errors = '';
    child.stdout.on('data', (data) => { output += data; });
    child.stderr.on('data', (data) => { errors += data; });
    const timer = setTimeout(() => child.kill(), 45000);
    const code = await new Promise((resolve, reject) => { child.on('error', reject); child.on('exit', resolve); });
    clearTimeout(timer);
    const match = output.match(/data-browser-result="([^"]+)"/);
    if (!match) throw new Error(`Browser did not finish (${code}): ${errors.slice(-1000)}`);
    const result = JSON.parse(match[1].replaceAll('&quot;', '"').replaceAll('&amp;', '&').replaceAll('&#39;', "'"));
    if (result.error) throw new Error(result.error);
    console.log(`${result.passed.length} browser contracts passed: ${result.passed.join(', ')}`);
} finally {
    child?.kill();
    server.closeAllConnections();
    server.close();
    const resolvedProfile = path.resolve(profile);
    if (path.dirname(resolvedProfile) !== path.resolve(tmpdir()) || !path.basename(resolvedProfile).startsWith('travel-compass-browser-')) {
        throw new Error('Refusing cleanup outside the browser test temporary directory');
    }
    await rm(resolvedProfile, { recursive: true, force: true, maxRetries: 10, retryDelay: 200 });
}
