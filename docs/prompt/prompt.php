Toto je aktuální kód:

<?php
require_once __DIR__ . "/PrompterHelper.php";
(new PrompterHelper(
    root: __DIR__ . '/../../',
    ignorePaths: ["docs",
    ".git",
    ".gitingnore",
    ".vscode",
    ".DS_Store",
    "build",
    "node_modules",
    "package-lock.json",
    ]
))->dumpWorkspace();
?>

Dostal jsem chybu v prohlížeči:

client.js:38 Uncaught (in promise) TypeError: Cannot assign to read only property 'myId' of object '[object Module]'
    at Socket.<anonymous> (client.js:38:44)
    at Emitter.emit (index.js:136:20)
    at Socket.emitEvent (socket.js:553:20)
    at Socket.onevent (socket.js:540:18)
    at Socket.onpacket (socket.js:508:22)
    at Emitter.emit (index.js:136:20)
    at manager.js:217:18
(anonymous) @ client.js:38
Emitter.emit @ index.js:136
emitEvent @ socket.js:553
onevent @ socket.js:540
onpacket @ socket.js:508
Emitter.emit @ index.js:136
(anonymous) @ manager.js:217
Promise.then
(anonymous) @ globals.js:4
ondecoded @ manager.js:216
Emitter.emit @ index.js:136
add @ index.js:142
ondata @ manager.js:203
Emitter.emit @ index.js:136
_onPacket @ socket.js:259
Emitter.emit @ index.js:136
onPacket @ transport.js:99
callback @ polling.js:79
onData @ polling.js:82
Emitter.emit @ index.js:136
_onLoad @ polling-xhr.js:193
xhr.onreadystatechange @ polling-xhr.js:129
XMLHttpRequest.send
_create @ polling-xhr.js:139
Request @ polling-xhr.js:75
request @ polling-xhr.js:253
doPoll @ polling-xhr.js:52
_poll @ polling.js:59
onData @ polling.js:89
Emitter.emit @ index.js:136
_onLoad @ polling-xhr.js:193
xhr.onreadystatechange @ polling-xhr.js:129
XMLHttpRequest.send
_create @ polling-xhr.js:139
Request @ polling-xhr.js:75
request @ polling-xhr.js:253
doPoll @ polling-xhr.js:52
_poll @ polling.js:59
doOpen @ polling.js:19
open @ transport.js:47
_open @ socket.js:197
SocketWithoutUpgrade @ socket.js:150
SocketWithUpgrade @ socket.js:565
Socket @ socket.js:725
open @ manager.js:115
Manager @ manager.js:41
lookup @ index.js:33
(anonymous) @ client.js:6Understand this error
render.js:78 Uncaught ReferenceError: canvas is not defined
    at drawPlayers (render.js:78:48)
    at gameLoop (render.js:11:5)
    at render.js:16:33