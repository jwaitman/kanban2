self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open('kanban-cache-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/index.html',
        '/src/main.js',
        '/src/assets/css/style.css'
      ]);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
