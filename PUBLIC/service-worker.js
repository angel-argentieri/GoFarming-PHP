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
    '/FRONT/js/auth.js'
];

// 1. Instalação: armazena os arquivos estáticos
self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE)
            .then(cache => cache.addAll(ARQUIVOS))
            .then(() => self.skipWaiting())
    );
});

// 2. Ativação: remove caches antigos de versões anteriores
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Interceptação de requisições
self.addEventListener('fetch', e => {
    // Ignora chamadas que não sejam GET (ex: POST de login, cadastro, regas)
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);

    // Se for requisição ao backend PHP/API, busca sempre da rede (Network-First)
    if (url.pathname.includes('/PUBLIC/') || url.pathname.endsWith('.php')) {
        e.respondWith(
            fetch(e.request).catch(() => caches.match(e.request))
        );
        return;
    }

    // Para arquivos estáticos (HTML, CSS, JS), mantém Cache-First
    e.respondWith(
        caches.match(e.request).then(cached => cached || fetch(e.request))
    );
});