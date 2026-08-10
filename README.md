# MiDy

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

![Coded by humans](by-humans.png)

A tool for building **Mi**ldly **Dy**namic websites, with more features than you would expect.

MiDy is in beta.  It's fairly stable and usable for small sites, but please don't trust it to anything enterprise-grade or mission-critical yet.  For a personal blog or small company site, it's ready.  Feedback welcome.

## Who is this for

MiDy sits between static site generators and full CMSes and frameworks.  It's for sites that are mostly static, and only "mildly dynamic."  Some listings are auto-generated, there are a few forms, etc.  It's for sites that would be a static site generator, except for that one annoying page where you can't quite do everything at compile time...

In practice, it can also be used as a Latte-and-Markdown-based static site generator.  Or a little of each, which is where the real power comes from.

## How it works

MiDy is built on the following assumptions:

* Most pages on a site are boring, from a technical point of view.
* The site editor can write HTML, and wants control over the layout of the site and individual pages, independently of each other.
* Alternatively, Markdown is a comfortable format to use.

To that end, MiDy doesn't have "controllers" the way many frameworks do.  Instead, most pages are simply files within the `routes` folder, in a file tree.  A request to `/foo/bar/baz` will end up at `/foo/bar/baz.md`, for example, and that markdown page will be rendered.  If instead there is a `/foo/bar/baz.latte`, then the Latte template will be rendered.  You can do basically whatever you want in the template.

MiDy supports five "page handlers":

1. Static files.  A list of supported static files is provided by default but can be easily overridden.  These files will simply be served as-is.
2. Latte template files.  These files will be rendered and the output sent back as a page.
3. Markdown files, rendered through Latte.  Markdown files will be rendered as Markdown, and the result passed to a standard Latte template, which will then be rendered.
4. PHP files.  For when you really do need dynamic behavior (eg, form submission), a route can be a PHP class.  Every HTTP method that is supported maps to a method of the same name.  So if you want to support `PUT`, have a `put()` method.  If not, omit it.
5. Link files. For when you want a path that just sends a redirect to somewhere.  The file itself is very simple YAML.  By default, they won't show in the page tree, but you can easily set `hidden: false` in the file, and it will show like any other page.  (Remember to set a `title`.) 

Additionally, the paths on disk don't have to 100% match the paths in the URL.  A sorting prefix, either date or arbitrary number, will be stripped from the URL.  So this page tree:

```text
/routes
  /index.md
  /01_about.latte
  /02_projects.latte
  /03_company.latte
```

Will produce URL paths of `/about`, `/projects`, and `/company`.  When a template builds a listing of pages, it will be sorted in numeric order.

It's also possible to "flatten" a directory.  That is mainly useful when you want to have, for instance, a `blog` directory with hundreds of blog posts, but to make keeping track of them easier you want to organize them into sub-folders by year, but not have that appear in the page tree.  Or organize them by author on disk, but not in the URLs.

The `index.md` file in the above example will be used as the "file" representation of the folder it is in.  So in this example, `index.md` is the home page of the site.  If you wanted to use a custom Latte template instead, change it to `index.latte` and do with it as you will.

## Running

MiDy requires PHP 8.5 and SQLite.  It needs no other services or extensions.

The best way to use MiDy is to use the starter skeleton, available on Packagist:

```shell
composer create-project crell/midy-skeleton mysite
```

That will create a new empty project with almost no files in it, just a starter skeleton.  All of MiDy itself is included as a composer dependency, making future upgrades easier.

Under php-fpm, MiDy boots and handles a single request. Under [FrankenPHP worker mode](https://frankenphp.dev/docs/worker/) MiDy boots once and handles requests in a loop instead.

To run MiDy another way, such as for RoadRunner or Swoole, override `run()` on your `class App extends MiDy` class.

## Setup

MiDy should be usable right out of the box, but many parts may be reconfigured.

The only really mandatory setup is setting your `BASE_URL`.  For security reasons, any absolute URLs generated that point to the current site use a fixed base URL, rather than deriving it from the request.  (Most frameworks do the same.)  The easiest way to set the base URL is via an environment variable named `BASE_URL`.   There is support for a `.env` file included if you want to go that route, though setting it via your hosting provider is preferred.

```ini
# .env
BASE_URL=https://example.com/
```

There are five paths that matter, all of which have a reasonable default but may also be overridden by an environment variable if desired:

| Directory                       | Default         | Env Var override |
| ------------------------------- | --------------- | ---------------- |
| Caches                          | ./cache         | CACHE_PATH       |
| Routes and pages (your content) | ./routes        | ROUTES_PATH      |
| Configuration files             | ./configuration | CONFIG_PATH      |
| Site Templates                  | ./templates     | TEMPLATES_PATH   |
| Public web pages                | ./public        | PUBLIC_PATH      |

In 99% of cases you should not need to change these.

The cache directory will need to be writeable by the web server.  The others may be readonly in production if you are using a Git deployment approach.  If you'd rather just edit the content directly on the production server, that will need to be writeable as well.

### Configuration

All configuration files are optional and are pretty small.  The only really necessary one is `template-variables.ini`, which allows you to specify arbitrary additional values to be made available to all templates.  Here's where you'd include, say, `siteName = Your Site Name`, to make that available to templates.

All configuration files are based off of classes and map to them exactly.  They can all be found in the [`src/Config`](src/Config) directory (or `vendor/crell/midy/src/Config` if you're using the skelton as recommended).  At this time only `.ini` files are supported, but if there is demand that could very easily be expanded to other formats.

### Content

All of your content lives in the `routes` path.  By default, it maps 1:1 to the URL path for that file, minus the file extension.  So a file named `routes/foo/bar/baz.md` will have a URL path of `/foo/bar/baz`.

All files (except static) have available frontmatter, though the format varies slightly with each file type.  All are generally optional, though many are recommended.  The Markdown example below shows all the first-class supported properties.

#### Markdown

For Markdown files, it's a typical YAML header:

`example.md`:
```markdown
---
title: The page title.
summary: An optional "short summary" of the page.
publishDate: The date and optionally time this page is published.
lastModifiedDate: The date and optionally time this page was last updated.
tags: A YAML array of tag strings for this page.
hidden: `true` or `false`.  Defaults to false.  If true, this page will not be shown in menu listings by default but may still be linked to directly.
routable: `true` or `false`.  Defaults to true.  If false, this file will not be accessible at all.
---

# An H1 here is interpreted as a title, but overridden by a frontmatter title.

<!--summary-->
Content in the page that is between these two comments will be part of the page
content, but also used as the `summary` property.  If you want the summary to be
different from the start of the page, use the `summary` frontmatter property.  If both
are specified, the frontmatter header wins.
<!--/summary-->

The rest of your Markdown here.
```

For `publishDate` and `lastModifiedDate`, any format readable by PHP's date and time handling is acceptable though `YYYY-MM-DD H:i:s` is strongly recommended.  If not specified, `publishDate` defaults to the file's creation date/time on the file system, and `lastModifiedDate` defaults to the file's last-modified date/time on the file system.

#### Latte

For Latte template files, the same YAML header is made available in a comment:

`example.latte`:
```latte
{layout template('layout.latte')}
{* Type-specify the parameters the template is expected to get, for type hinting. *}
{varType \Crell\MiDy\PageTree\Page $currentPage}

{*---
title: Test Page
other properties here
---*}

{define title}{$currentPage->title}{/define}

{block content}
The rest of your Latte template here.
{block content}
```
#### Link

Link pages are just a YAML file, with two additional properties:

`example.link`:
```yaml
title: The page title.
location: /go/to/temporary
code: 307
```

Any 3xx code is allowed, though 307 (A temporary redirect) and 308 (permanent redirect) are recommended.  The default is 308 (permanent).  The redirect will be to the value of `location`.

#### PHP

For PHP pages, there is an attribute available instead.

`example.php`:
```php
use Crell\MiDy\PageTree\Attributes\PageRoute;

#[PageRoute(title: 'The page title here')]
class DoesntMatter
{
    public function __construct(
        private readonly ResponseBuilder $builder
    ) {}

    public function get(ServerRequestInterface $request): ResponseInterface
    {
        // Your logic here.
    }

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        $form = $request->getParsedBody();
        return $this->builder->ok("POST received: " . $form['name']);
    }
}
```

Note that the class name and namespace of a PHP route file are entirely irrelevant.  It will be loaded on the fly and the necessary dependencies injected into the constructor automatically.  As long as the name is unique across the site, you're fine.

#### Static

Static pages (such as images) do not have frontmatter, and are by default routable and hidden (so you can link to them, but they don't appear in listings).

The exception is a file that ends in `.html`.  In that case, the page's `<title>` tag will be parsed out and used as the page title.

#### Other frontmatter

In the PHP attribute, there is an `other` array that can accept arbitrary key/value pairs.  For all other frontmatter formats, arbitrary additional properties can simply be listed alongside the others.  They will still be pulled into the `other` property on the page, and made available in the template for use however you'd like.

For example, to have a completely separate open graph description in addition to the summary, one could do:

```yaml
title: My page
summmary: Some long description
og_description: A short description
```

```latte
{* In the page template that renders that file... *}
{varType \Crell\MiDy\PageTree\Page $currentPage}

{block links}
    {include parent}

    <meta property="og:description" content="{$currentPage->other['og_description'] ?? $currentPage->summary}">
    {* ... *}
{/block}
```

### Advanced content

Beyond just creating content, there are many ways to organize it.

#### Ordering by prefix

By default, all listings will list pages ordered alphabetically by their title, then by their path.  However, the file name may also include ordering information.

If a page file begins with any number followed by a `_`, that will be taken as an order value and stripped off of the path name.  Leading zeros are ignored, as are `-` in the number, so that dates may be allowed.

So for example:

```text
/foo
  /01_contact.md
  /02_beliefs.md
  /04_demo.md
  /04_about.md
/blog
  /2026-02-14_happy-valentines-day.md
  /2026-03-15_happy-ides-of-march.md
  /2026-07-04_happy-4th.md
```

That will result in paths of:

* `/foo/contact`
* `/foo/beliefs`
* `/foo/demo`
* `/foo/about`
* `/blog/happy-valentines-day`
* `/blog/happy-ides-of-march`
* `/blog/happy-4th`

And listing queries in a template will order them in the order above first, before alphabetical.

#### `folder.midy`

This file may be placed in any content directory.  It will always be hidden but allows setting configuration for files in that directory.

```yaml
hidden: false
order: Asc
flatten: false
defaults:
    title: 'Testing'
    template: 'blog-page.latte'
```

The `hidden` property applies to the directory.  If `true`, the directory itself will be hidden from listings by default.  It defaults to `false`.

The `order` property may be either `Asc` or `Desc` (case-insensitive).  If `Desc`, then the ordering logic described above is reversed.  That is particularly useful when listing events or posts in reverse chronological order.

The `flatten` property, if set to `true` (default is `false`), will cause all paths beneath this folder to be omitted, and the system will see all files below this directory as belonging to this directory.  That is useful mainly when there is a very large number of files that live at the same "level" from a URL perspective, but you want an easier way to organize them on disk.  That includes large blog archives, large product catalogs, etc.

So, for example:

```text
/blog
  /folder.midy
  /2025/site-launch.md
  /2025/now-hiring.md
  /2025/merry-christmas.md
  /2026/2026-02-14_happy-valentines-day.md
  /2026/2026-03-15_happy-ides-of-march.md
  /2026/2026-07-04_happy-4th.md
```

If `flatten` is `true` in `blog/folder.midy`, then the resulting URLs will be:

* `/blog/site-launch.md`
* `/blog/now-hiring.md`
* `/blog/merry-christmas.md`
* `/blog/happy-valentines-day.md`
* `/blog/happy-ides-of-march.md`
* `/blog/happy-4th.md`

Finally, the `defaults` block replicates the same front-matter as pages themselves, and provides fallback default values for any file in that folder (or its descendants if `flatten` is enabled).  The most useful default property is `template`, which specifies which page template to use for Markdown files after they've been rendered.  However, it can also be used to set a tag across all files in a given page, for example.

### Templating

Templating is provided by the [Latte template engine](https://latte.nette.org/en/).  (If there's interest, I can explore supporting Twig as well, though doing both at once could be tricky.)  If you've used Twig, it's very similar but uses a more PHP-ish syntax.  Several enhancement functions are included as well.

When referencing another template (such as for extending from a common layout), rather than referencing the template file as a direct path, use the `template()` function and a file name.  That will look up the file from a series of configurable directories, with each overriding the one previous.  That way, MiDy can ship with a set of basic core templates (like `html.latte` for the HTML page itself), a downloadable theme can provide a broader look and feel, and an individual site can provide its own templates that override some or all of those provided.

A typical Latte route page will look something like this:

```latte
{* Specify the layout file to use.  Aka, parent template. *}
{layout template('layout.latte')}

{* Type-specify the parameters the template is expected to get, for type hinting. *}
{varType \Crell\MiDy\PageTree\Page $currentPage}

{*---
YAML Frontmatter here, much like Markdown files often have.  Always include a title.
title: Title of the page.
---*}

{define title}{$currentPage->title}{/define}

{block styles}
{* Any extra CSS files you want to inject into the page head on this page only. *}
{/block}

{block content}
    Whatever the heck you want here, as the body of the page.
{/block}
```

There are a few other important functions included.

### `page('/foo/bar')`

This function returns the `Page` object for whatever page is at the specified path.

### `folder('/foo/bar')`

This function returns a `Folder` object.  It is similar to a `Page` object, and if that folder has an index file it can be treated as one, but it can also be iterated to get a list of all the pages in that folder.

### `pageQuery()`

This function allows read (but not write!) access to the cached index of pages.  It should always be called with named arguments, but allows you to search by folder (shallow or deep), tag, publication date, whether a page is hidden, and other options.  All results are paginated by default, and the pagination size is configurable as well.  Ordering is also configurable.

`pageQuery()` can be used to build blog index pages, upcoming events feeds, or a site tree of the entire site.

The return value of `pageQuery()` is a [`Pagination`](src/PageTree/Pagination.php) object.  It contains all necessary information about the pagination, as well as a collection of `Page` objects that matched the query.

### `pageUrl(Page $page)`

This function accepts a `Page` object and returns its full URL as a string.  Two optional arguments are `query` (to provide a list of query parameters to add) and `full` (to force the URL to include the complete URL including `https://...`).

### `atomId(Page $page)`

This function generates a unique ID for a given page following the format required for Atom feeds.  See [RFC 4151](https://datatracker.ietf.org/doc/html/rfc4151) for details, or just use this function when builiding an Atom feed and ignore the details.

## Shell commands

A few basic management commands are included as composer executables.

### `vendor/bin/midy-clean`

Deletes all cache files and generated static files.

### `vendor/bin/midy-reindex`

Rebuilds the SQLite index of the entire site.  This is built incrementally by default, but if you can build it in advance, that will be faster.

### `vendor/bin/midy-build-static`

Pre-renders all static files (images, JS, CSS, etc.) to the `public` directory, so they can be served directly by the web server without going through PHP.

### `vendor/bin/midy-build-all`

Pre-generates the entire site, excluding PHP pages.  If there are no PHP pages, then the result is a `public` directory that you can upload on its own somewhere as a fully static site.  (Though probably remove the `index.php` file first.)

## Contributing

MiDy is still in active development.  Please try it out, poke around, kick the tires, and otherwise see how it could be made better.  If you have suggestions, please either open an issue or reach out to me on the [PHPC Discord](https://phpc.chat/) server.

To file PRs against MiDy, you can run this repository locally:

1. Clone this repository
2. Run `docker compose build && docker compose up -d`
3. Run `./Taskfile shell` to open a shell on the fpm container.
4. Run `composer install`
5. Go to `http://localhost:30001` (nginx -> php-fpm) in your browser and get a 404 page. :-)
6. Go go `http://localhost:30002` (frankenphp) in your browser and get a 404 page. :-)
7. Now start populating the `/routes` folder with your content!

See the [`tests/test-routes`](tests/test-routes) folder for many examples.  (That's the fixture used for integration tests.)

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security-related issues, please use the [GitHub security reporting form](https://github.com/Crell/MiDy/security) rather than the issue queue.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

## License

The AGPL version 3 or later. Please see [License File](LICENSE.md) for more information.

Note, however, that any content or templates you use with MiDy are explicitly exempt from license coverage, regardless of whether they could technically be covered.  Your content belongs to you.  MiDy belongs to the world.

[ico-version]: https://img.shields.io/packagist/v/Crell/MiDy.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/License-GPLv3-green.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/MiDy.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/MiDy
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/MiDy/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/MiDy
[link-downloads]: https://packagist.org/packages/Crell/MiDy
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
