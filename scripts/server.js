const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = 8000;
const ROOT = path.resolve(__dirname, '..');

const MIME_TYPES = {
    '.html': 'text/html; charset=UTF-8',
    '.css': 'text/css; charset=UTF-8',
    '.js': 'application/javascript; charset=UTF-8',
    '.json': 'application/json; charset=UTF-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.xml': 'application/xml; charset=UTF-8',
    '.txt': 'text/plain; charset=UTF-8',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
    '.ttf': 'font/ttf',
    '.eot': 'application/vnd.ms-fontobject'
};

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url);
    let pathname = decodeURIComponent(parsedUrl.pathname);

    if (pathname.endsWith('/')) {
        pathname += 'index.html';
    }

    const filePath = path.normalize(path.join(ROOT, pathname));

    // Security check: ensure path is within ROOT (case-insensitive for Windows)
    if (!filePath.toLowerCase().startsWith(ROOT.toLowerCase())) {
        res.writeHead(403, { 'Content-Type': 'text/plain' });
        res.end('403 Forbidden');
        console.warn(`[403] Forbidden access attempt: ${pathname}`);
        return;
    }

    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end(`404 Not Found: ${pathname}`);
            console.warn(`[404] Not Found: ${pathname}`);
            return;
        }

        const ext = path.extname(filePath).toLowerCase();
        const contentType = MIME_TYPES[ext] || 'application/octet-stream';

        res.writeHead(200, {
            'Content-Type': contentType,
            'Content-Length': stats.size,
            'Access-Control-Allow-Origin': '*',
            'Cache-Control': 'no-cache'
        });

        const stream = fs.createReadStream(filePath);
        stream.pipe(res);
        stream.on('error', (streamErr) => {
            console.error(`[500] Stream error on ${pathname}:`, streamErr);
            if (!res.headersSent) {
                res.writeHead(500, { 'Content-Type': 'text/plain' });
                res.end('500 Internal Server Error');
            }
        });
        console.log(`[200] ${req.method} ${pathname}`);
    });
});

// Listening without host argument defaults to dual-stack IPv4/IPv6 (::)
server.listen(PORT, () => {
    console.log(`LHI Local Server running on http://localhost:${PORT}/ and http://127.0.0.1:${PORT}/`);
    console.log(`Document Root: ${ROOT}`);
});
