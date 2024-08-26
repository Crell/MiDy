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

--------------------------

OK, evented router replaced with something else.

--------------------------

Problem: Every file that will appear in the page tree needs metadata.  That's easy enough for Markdown files, but not for Latte or static files.  To say nothing of PHP files!

Metadata we need, at minimum:

* Title
* Tags
* Sort order
* Publish date?

Sort order is the really tricky one.  Ideally that can be done with the file name a la Grav, or with date stamps, or something.  But then translating a path to a file gets way, way trickier.

For some files, a paired .meta file or similar with YAML could work.  But that will scale badly if you're building mostly Latte pages, or static, or anything but Markdown, really.

It might be possible to squeeze a {frontmatter} block into Latte files, but that doesn't solve the main issue.  Excrement.

Even then, performance becomes an issue.  Trying to query across the file system will be not-fast, unless it gets cached very effectively.  But with hand editing of files, it's really hard to know when to update the cache.

So what data could be squeezed into the file system naming, in a consistent fashion, given the plan to have different paths have different rules???

```php
glob('foo/bar/*.*');
```

Thought: Just like different paths can have a different router, they can also have a different provider.  Or, maybe the provider is all we need?  The provider handles determining what paths map to what files in that subtree.

Caching could be done at any level as needed.  Does the route provider differ from the handler?  Maybe...  For now, let's try just eating the cost of no-caching and see what happens.

The page tree should then be lazy!  That way it can pull from selected parts of the tree as needed, only as far as needed.

$page->children(): array<Page>

-----------------

The delegating path provider thingie seems to be working so far, give or take some renaming and caching.  The problem is I'm still not sure how that impacts routing in a performant way.  I also still don't know how to handle fancier PHP paths.

Idea: Build a find() command into Folder, which takes a glob and passes that to the appropriate providers.  How does that work with nested provider paths?

I'm also still unclear exactly how to handle different file types.  I will probably need to have each file type have its own definition class, which may or may not be an instantiated child of Page.

PHP file loading is still an open question, as is how to deal with off-brand extensions (sitemap.xml, etc.)  PHP file paths also dstill don't support placeholders, only GET/POST, which is sub-optimal.  But doing otherwise seems highly weird in this model.

Also still need to write a Form body deserializer, likely as a Param converter.

So next tasks are:

1. ParamConverter for form bodies.  (These should probably get renamed ArgumentConverter?)
2. find() for Folder.  Returns a PageList, probably.
3. Use find() in the router, see what happens.
4. Class-per-file-type handlers.  Unclear how that overlaps with the existing routing handlers.  Probably merges with them?
5. Tests for form handling.

Fun fact: The Page tree system treats multiple same-extension files as a single entry, because it indexes just by name.  That... may be OK?  TBD.

For paths:

/ = 1
/foo = 2
/foo/bar = 3

path: /baz
current: 1
relevant: 1

path: /
current: 1
relevant: 1, 2, 3

path: /foo
current: 2
relevant: 2, 3

path: /foo/bar
current: 3
relevant: 3

path: /foo/bar/beep
current: 3
relevant: 3

--------------

OK, new strategy.  The PageTree just made life way too difficult.  Instead, let's try Flysystem.

It MAY be possible to build a "flattened adapter" that extends/wraps the LocalFilesystemAdapter and does the flattening.  It would have to override not just the lister, but also the reader. Hm.

For a prefix order number, this will also require a new adapter.  It will need to extend LocalFileystemAdapter and override listContents(), replicating it entirely but using a different iterator.  (The iterator it uses is private, which sucks.)  That iterator will be a custom version of LocalFilsystemAdapter::listDirectory() that strips off numbers and orders by them.

Then I'd also need a MultiplexAdapter to handle different paths working differently.  Sounds... I think doable.


The bigger question is if this approach will get me the info I need in the router efficient.  I hope so, but...  I probably won't know without trying it.  Though I could try doing the "unadulterated" version first, before writing any adapters, to see if that much works at least.  That is probably wise.


For PHP file loading, new strategy:

* Define a concrete class, with DI
* On routing, use ClassFinder to find the class the file defines, then manually require the file, then use $container->make() (which PHP-DI apparently does do) to load it.  That should handle the injection.
* Then return the callable reference, the same as now.

----------------------

OK, streams don't work.  Cache it all.

Every folder gets its own cache entry.
The folder's child pages are embedded in it.
The folder's child directories are represented as stubs, that point to another folder entry.
That means every folder still needs access to the cache front-end to look up the folders lazily.
This does mean lookups need to go through every folder... Hm.  Future optimization.

FolderRef is what gets traversed.  It contains a lazy-loaded folder object, which has info on all pages in that folder.  Same interface, passthru.

if (cached file mtime < real file mtime) {
  // need to reindex.
}

Tree(Data)
--Node
--Node
--Tree(Data)
----Node
----Tree

Data has list of nodes, and stubs for sub-trees.  So... that means three different objects for branches: Logical, stored, and ref, where ref gets lazy-loaded into a logical.  The cache is only ever referenced on the logical object.

-----------------------------

FolderWrapper
--count
--iterate
--children (alias iterate)
--find
--child($name)
--title
--path
FolderData
--count (could skip)
--iterate (not using?)
FolderRef
Page
--limitTo (this is awful)
--variants
--variant($ext)
--title
--path
RouteFile
--?

PageCache

That's a lot of data structures...  And we may need more for the different file types.  Blargh.

Different file types would extend from Page.  Or... actually RouteFile.  Does that mean Page is a pseudo-RouteFile?

FolderRef is just a stub, so it needs no interface.

FolderWrapper... should probably just be Folder.  FolderData is fully internal, so doesn't need a shared interface.  No one should ever be seeing it directly.

Folder->x
FolderWrapper->Folder
FolderData->?
interface Titled (Folder, Page)
interface Linkable (Folder, Page, RouteFile), maybe extends Titled?

RouteFile
--::fromSpl()
--title
--path
--mtime
--ext
--physicalFile (needed for routing)

Page
--title
--path
--mtime

A Folder should be represented page-ish as its index file, if any.  If not, fall back to its folder name, I guess?

Still need to design magic .midy file for extra frontmatter.

How can Page even have a title?  Its title is based on which variant is active, but we don't know that without routing.  Having those only work if there's only one variant is a land mine.  path() works, though.  Sort of.  But for static files it should probably include the extension.  Which is then variant dependent.  Arf.

Page title could be just the path segment, if there's more than one file?  But no, that breaks blog posts with commonly named images, or forms with a same-name processor.  Maybe it could be limited to pull from RouteFiles with specific interfaces, indicating there is metadata?

RouteFile implements Linkable, not Titled
|-StaticFile implements Routable
|-PhpFile implements Routable, maybe title?
|-LatteFile implements Routable, HasTitle
|-MarkdownFile implements Routable, HasTitle
 |-MarkdownLatteFile

... Do I need to integrate language variants now?  Yeesh.  I hope not.

