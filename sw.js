// sw.js - Service Worker Ligero (Sin caché)

self.addEventListener('install', (event) => {
    // Se instala inmediatamente sin guardar archivos para offline
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Toma el control de la pantalla de inmediato
    event.waitUntil(self.clients.claim());
});

// Receptor de Notificaciones en Segundo Plano
self.addEventListener('push', function(event) {
    const opciones = {
        body: event.data ? event.data.text() : 'Tienes un nuevo arreglo pendiente de revisar.',
        icon: 'icono-192.png',
        badge: 'icono-192.png',
        vibrate: [200, 100, 200]
    };

    event.waitUntil(
        self.registration.showNotification('¡Nueva Solicitud!', opciones)
    );
});