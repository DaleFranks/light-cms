# Cards and Modules

Both are pieces of content written once and placed in pages with a shortcode, so they can be reused and changed in one place.

## Cards

A card is a small box, such as a service, a price plan or a call to action. Cards are often shown side by side.

- **Name**: for finding it in the admin.
- **Slug**: used in the shortcode: `[card:hosting]` for one card, or `[cards: design, development, seo]` for several in a row.
- **Style**: plain, or a colored panel: **Dark**, **Light** or **Primary color**. The panel colors are set on the Theme screen's **Panel styles** tab.
- **Featured**: highlights the card with a colored border and a "Featured" ribbon.
- **Content**: Markdown. Images, videos and forms can be used inside a card.

## Modules

A module is a larger section, such as a call-to-action band or a contact section. Place one in a page with `[module:slug]`. On a page with the **Landing** layout, modules stretch across the whole window as bands.

Modules have a name, slug, style and content, like cards.

## The footer

The footer is a module too, shown at the bottom of every page. It's always in the Modules list and can't be deleted or renamed. Its color is set on the Theme screen's **Footer** tab, which also has a link straight to it.

It can use these shortcodes:

- `[navigation:flat]`: a row of links to the pages in the menu
- `[navigation:multi]`: a column for each page in the menu, with its sub-pages under it
- `[curr-year]`: the current year
- `[site-name]`: the site name
