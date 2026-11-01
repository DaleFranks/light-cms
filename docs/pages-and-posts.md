# Pages & Posts

Everything with its own address on the site is listed here. There are three types:

- **Page**: an ordinary page, such as About or Contact
- **Blog**: a page that lists its posts, newest first (a site can have several blogs)
- **Post**: an article that belongs to one blog. Its address is the blog's followed by its own, such as `/articles/my-first-post`

## Writing a page or post

- **Title**: shown at the top of the page and in the browser tab.
- **Slug**: the page's address. It's filled in from the title as you type, and numbered if that address is taken (`about-2`). Pages and blogs can use slashes to sit "under" another address, such as `services/design`. A post's slug is just its own part.
- **Content**: written in Markdown, with shortcodes. See [Writing content](writing-content.md).
- **Search engines**: an optional description and keywords. The description is often shown under the page's title in search results.

The page with the slug **`home`** is the home page, shown at the site's main address.

Some addresses are used by the CMS itself and can't be taken: `admin`, `setup`, `uploads`, `forms` and a few others.

## Publishing

- **Status**: **Draft** pages are only visible in the admin; **Published** ones are on the site.
- **Publish date**: leave it empty to publish straight away. A date in the future schedules the page or post: it appears on its own at that time. For posts, the date is shown with the post and in the blog's list.

When a published page's address changes (its slug, or its blog's), a [redirect](redirects.md) from the old address is added automatically, so links and search results keep working.

## Layout

- **Standard**: the title, then the content in a column as wide as set on the Theme screen.
- **Full width**: the title, then the content as wide as the header.
- **Landing**: no title, and modules stretch across the whole window as bands. Start the page with a module that has its own heading.

## The menu

Pages and blogs can appear in the menu at the top of every page (posts can't):

- **Menu order**: a number; lower numbers come first. Leave it empty to keep the page out of the menu.
- **Parent**: put the page in a dropdown under another menu item.
- **Menu label**: a shorter name for the menu, if the title is long.

When the menu doesn't fit beside the logo (and always on phones), it folds into a menu button.

## Blogs

A blog's page shows its own content first, then its published posts as cards: each post's first image, its title, date, and the first three sentences.

- **Posts per page** (in the Blog box): how many posts are listed before going on to a next page. It starts at 9, which fills three rows of three.

A blog that has posts can't be deleted or changed to another type; move or delete its posts first.
