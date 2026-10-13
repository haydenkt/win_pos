const CACHE='winpos-survey-shell-v2';
const FILES=['index.html','app.css','app.js','manifest.webmanifest','icon.svg'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(FILES))));
self.addEventListener('activate',event=>event.waitUntil(Promise.all([
 caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('winpos-survey-shell-')&&k!==CACHE).map(k=>caches.delete(k)))),self.clients.claim()
])));
self.addEventListener('fetch',event=>{
 const url=new URL(event.request.url),root=new URL(self.registration.scope);
 if(event.request.method!=='GET'||url.origin!==root.origin||!url.pathname.startsWith(root.pathname))return;
 const file=url.pathname.slice(root.pathname.length)||'index.html';
 // Cache only the public static shell. Never cache sessions, API, photos or POS pages.
 if(!FILES.includes(file))return;
 event.respondWith(caches.open(CACHE).then(async cache=>await cache.match(file)||fetch(event.request)));
});
