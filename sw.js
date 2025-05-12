const CACHE_NAME = 'qlnhansu-cache-v1';
const urlsToCache = [
  '/qlnhansu_V3/',
  '/qlnhansu_V3/backend/src/public/css/notifications.css',
  '/qlnhansu_V3/backend/src/public/css/loading.css',
  '/qlnhansu_V3/backend/src/public/admin/payroll/payroll.css',
  '/qlnhansu_V3/backend/src/public/admin/payroll/payroll.js',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/back.png',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/salary.png',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/present.png',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/money.png',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/bill.png',
  '/qlnhansu_V3/backend/src/public/admin/payroll/pic/reload.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
}); 