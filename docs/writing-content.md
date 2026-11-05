# Writing content

Pages, posts, cards and modules are written in **Markdown**, a simple way of formatting text with ordinary characters. The editor has buttons for the common formatting.

## Markdown basics

| Write | To get |
|---|---|
| `## A heading` | A heading (`#` is the largest, `######` the smallest) |
| `**bold**` and `*italic*` | **bold** and *italic* |
| `[our services](/services)` | A link. Addresses on your own site can start with `/`; if the site is in a folder (such as `example.com/blog`), the folder is added automatically. |
| `- item` on each line | A bulleted list |
| `1. item` on each line | A numbered list |
| `> quoted text` | A quotation |
| A blank line | A new paragraph |

HTML can also be used where Markdown isn't enough.

A page's title is already shown above its content, so if the content starts with a `# Heading` line, that line is left out.

## Shortcodes

Shortcodes are written in square brackets and are replaced with something when the page is shown. A shortcode naming something that doesn't exist is shown as written, so a mistake is easy to spot.

### Images

```
[image:team.jpg]
[image:team.jpg|400|right|Our team at the office]
```

Places an image from the [image library](images.md). After the file name, in any order:

- a **width**: `400` (pixels), `50%`, or `400x300`
- an **alignment**: `left` or `right` (text wraps around the image, except on phones and small tablets, where the image is centered on its own line), or `center`
- **alt text** for screen readers. Without it, the alt text saved in the image library is used.

A full image address (`https://...`) works too. Ordinary Markdown images, `![Our team](team.jpg)`, also find their file in the library.

### YouTube videos

```
[youtube:https://www.youtube.com/watch?v=VIDEO_ID]
[youtube:https://youtu.be/VIDEO_ID|640|left]
```

Embeds a video. Optional: a width (`640` or `80%`) and an alignment (`left`, `center` or `right`; centered by default). A left or right video wraps like an image, and takes up 40% of the page's width if you don't set one. On phones and small tablets, it's centered on its own line at its set width, or the full width of the page if none is set, and shrinks if the screen is narrower than its set width.

### Forms

```
[form:contact]
```

Places a form made on the [Forms](forms.md) screen. Works in pages, posts, cards and modules.

### Cards, modules and sliders (pages and posts only)

```
[card:hosting]
[cards: design, development, seo]
[module:call-to-action]
[slider:home-slider]
```

- `[card:slug]` places one [card](cards-and-modules.md); `[cards: a, b, c]` places several side by side. Rows come out even: 2 or 3 cards share a row, 4 make two rows of two, and 5 or more go three to a row.
- `[module:slug]` places a [module](cards-and-modules.md).
- `[slider:slug]` places a [slider](sliders.md).

These can't be placed inside cards or modules.

### In the footer

The footer module has a few shortcodes of its own:

- `[navigation:flat]`: a row of links to the pages in the menu
- `[navigation:multi]`: a column for each page in the menu, with its sub-pages listed under it
- `[curr-year]`: the current year
- `[site-name]`: the site name set on the Theme screen
