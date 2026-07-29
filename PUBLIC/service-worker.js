const CACHE = 'gofarming-v1';

const ARQUIVOS = [
    '/FRONT/dashboard.html',
    '/FRONT/scan.html',
    '/FRONT/planta.html',
    '/FRONT/login.html',
    '/FRONT/cadastro.html',
    '/FRONT/css/style.css',
    '/FRONT/js/api.js',
    '/FRONT/js/dashboard.js',
    '/FRONT/js/scan.js',
    '/FRONT/js/planta.js',
    '/FRONT/js/auth.js',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(ARQUIVOS))
    );
});

self.addEventListener('fetch', e => {
    e.respondWith(
        caches.match(e.request).then(cached => cached || fetch(e.request))
    );
});
