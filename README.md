# MiDy

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

A tool for building **Mi**ldly **Dy**namic websites, with more features than you would expect.

MiDy is in alpha.  It works, and it's in a state that people can play with it, but it's not yet production-ready.  Feedback welcome.

## Who is this for

MiDy sits between static site generators and full CMSes and frameworks.  It's for sites that are mostly static, and only "mildly dynamic."  Some listings are auto-generated, there's a few forms, etc.  It's for sites that would be a static site generator, except for that one annoying page where you can't quite do everything at compile time...

In practice, it can also be used as a Latte-and-Markdown-based static site generator.  Or a little of each, which is where the real power comes from.

## How it works

MiDy is built on the following assumptions:

* Most pages on a site are boring, from a technical point of view.
* The site editor can write HTML, and wants control over the layout of the site and individual pages, independently of each other.
* Alternatively, Markdown is a comfortable format to use.

To that end, MiDy doesn't have "controllers" the way many frameworks do.  Instead, most pages are simply files within the `routes` folder, in a file tree.  A request to `/foo/bar/baz` will end up at `/foo/bar/baz.md`, for example, and that markdown page will be rendered.  If instead there is a `/foo/bar/baz.latte`, then the Latte template will be rendered.  You can do basically whatever you want in the template.

MiDy supports four "page handlers":

1. Static files.  A list of supported static files is provided by default, but can be easily overridden.  These files will simply be served as-is.
2. Latte template files.  These files will be rendered and the output send back as a page.
3. Markdown files, rendered through Latte.  Markdown files will be rendered as Markdown, and the result passed to a standard Latte template, which will then be rendered.
4. PHP files.  For when you really do need dynamic behavior (eg, form submission), a route can be a PHP class.  Every HTTP method that is supported maps to a method of the same name.  So if you want to support `PUT`, have a `put()` method.  If not, omit it.

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

MiDy requires PHP 8.4.  The easiest way to try it out is

1. Clone this repository
2. Run `docker compose build && docker compose up -d`
3. Run `./Taskfile shell` to open a shell on the fpm container.
4. Run `composer install`
5. Go to `http://localhost:30000` in your browser and get a 404 page. :-)
6. Now start populating the `/routes` folder with your content!

See the [`tests/test-routes`](tests/test-routes) folder for many examples.  (That's the fixture used for integration tests.)

## Templating

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

There are two other important functions included.

### `pageQuery()`

This function allows read (but not write!) access to the cached index of pages.  It should always be called with named arguments, but allows you to search by folder (shallow or deep), tag, publication date, whether a page is hidden, and other options.  All results are paginated by default, and the pagination size is configurable as well.  Ordering is also configurable.

`pageQuery()` can be used to build blog index pages, upcoming events feeds, or a site tree of the entire site.

The return value of `pageQuery()` is a [`Pagination`](src/PageTree/Pagination.php) object.  It contains all necessary information about the pagination, as well as a collection of `Page` objects that matched the query.

### `folder()`

This function returns a `Folder` object.  It is similar to a `Page` object, and if that folder has an index file it can be treated as one, but it can also be iterated to get a list of all the pages in that folder.

## Shell commands

These commands are still a bit rough, but provide useful site management tasks.

### `php clean.php`

Deletes all cache files.

### `php pregenerate-static.php`

Pre-renders all static files (images, JS, CSS, etc.) to the `public` directory, so they can be served directly by the web server without going through PHP.

### `php staticify.php`

Pre-generates the entire site, excluding PHP pages.  If there are no PHP pages, then the result is a `public` directory that you can upload on its own somewhere as a fully static site.  (Though probably remove the `index.php` file first.)

## Plans

While the task list is long, here's the main things still on my radar before 1.0:

* Gobs of performance improvements.  It's already pretty fast, but for instance it's rebuilding the container every request still.  That obviously can be improved.
* Make routing more flexible, including possibly argument path segments.
* Split most major components out to their own stand-alone LGPLv3 libraries.
* This thing is almost a framework, by design.  Factor the framework out as well, including an extension mechanism.
* Build a "skeleton" app, and move 99% of the code to composer packages used by that.  As little code as possible should be "in" a real site.
* Flesh out the shell commands a lot better.  Like, use a real command framework.
* Add support for redirection pages and link pages.
* Better form handling.
* Possibly switch to an event-based kernel rather than pure PSR-15 middlewares.
* A proper install process using `composer create-project`.
* Flesh out theme support better.
* Way more detailed documentation.

## Feedback

MiDy is still in active development and is not ready for production use, but it is stable enough to experiment with.  Please try it out, poke around, kick the tires, and otherwise see how it could be made better.  If you have suggestions, please either open an issue or reach out to me on the [PHPC Discord](https://phpc.chat/) server.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form](https://github.com/Crell/MiDy/security) rather than the issue queue.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

## License

The GPL version 3 or later. Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Crell/MiDy.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/License-GPLv3-green.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/MiDy.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/MiDy
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/MiDy/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/MiDy
[link-downloads]: https://packagist.org/packages/Crell/MiDy
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
