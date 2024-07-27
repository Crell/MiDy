The router needs to be efficient.

Handlers are similar to controllers, but not quite.

We want different handlers active in different paths, so that they can do arbitrary custom logic if needed.  (Eg, blog.)

There can be multiple files with the same base name.

Some handlers care about different methods.

A handler should declare which methods it supports.  For most, that's just Get.  For PHP, it's derived from the class method names.

... I think handlers will need to be pre-loaded.  Hopefully there's few enough of them.

So a handler knows what methods it supports... and what extensions it supports?  Presumably.

Which means we can/should ask the handler if it can handle the request.  Listing a handler before it will take care of overriding for select paths.  Damn, are we back to events?

But we also have to deal with path prefixes.  In that case, does that mean we fire the event multiple times?

No, we should have a list of what handlers can handle what extensions and methods.  That can... be built when adding handlers to the router?  That would be great in a persistent process but not great otherwise.

```php
addHandler($handler) {
  foreach ($handler->methods() as $method) {
    foreach ($handler->extensions() as $ext) {
      $this->map[$ext][$method][] = $handler;
    }
  }
}

foreach ($prefixes as $prefix) {
  foreach ($candidates as $candidate) {
    foreach ($this->map[$ext][$method] ?? [] as $handler) {
      if ($result = $handler->route($request)) {
        return $result;
      }
    }
  }
}
```

If we want a handler to be able to handle non-literal sub-paths, it HAS to be given information early enough.  That means the *handler* is responsible for identifying if the path matches a file(!).  Which is nutty.  But I don't want a base class.

Technically... there's no reason the handler needs to care about a file.  Fuckin'a.  I didn't want to be back to a traditional router.

class Handler {
  function route(ServerRequestInterface $request, RequestPath $requestPath) {
    
  }
}

... Because the path branching and the handle selection are different levels and should be separate objects!

OMG that worked!  Who knew?  Though I think the evented router still needs to change, as too much logic is living in the listeners/handlers.
