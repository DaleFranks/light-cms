# Images

The image library holds every image used in the site's content.

## Adding images

- Click **Upload images** and drop in one or more JPG, PNG, GIF or WebP files.
- Images can also be uploaded straight from the content editor, and in a slider's slides; they're added to the library too.

Names are tidied up (lowercase, no spaces). A name that's already taken gets a number added (`team-2.jpg`), so an upload never replaces an existing image.

Images are stored exactly as uploaded; they aren't resized or compressed. For fast pages, resize photos before uploading. A page's text column is usually about 800 to 950 pixels wide, so around 1000 pixels wide is plenty for most images; a photo straight from a phone or camera is far larger than that.

An image file over 500 KB shows **Large file** on the Images screen, and gets a notice after it's uploaded or replaced. It's only a reminder: the image is still saved as it is, and you can resize or compress it and use **Replace** at any time.

## Using an image

Each image's **shortcode** is shown in the list, such as `[image:team.jpg]`; click it to copy it. See [Writing content](writing-content.md#images) for setting its width, alignment and description.

## Replacing an image

**Replace** on an image swaps in a new file and keeps its name, so every page, shortcode and slider using it shows the new file, with its new width, height and size. Its description stays. The old file is overwritten and can only be brought back from a [backup](backup.md).

The new file must be the same type: a JPG can only be replaced by a JPG. To use a different type, upload it as a new image.

Visitors get the new file straight away, because the image's address gains a version (`photo.jpg?v=1730600000`). An address typed by hand into HTML or Custom CSS doesn't change, so a browser may show the old file until its saved copy expires.

**Refresh image details**, at the top of the screen, is for files replaced outside the admin, such as by FTP. It re-reads every file, updates the details of any that changed, and lists any that are missing.

## Editing and deleting

- **Description**: read aloud by screen readers, and shown if the image can't load. It's used wherever the shortcode doesn't give its own description.
- An image that's in use can't be deleted. The message says where it's used, so you can remove it there first.

The logo and browser tab icon are uploaded on the [Theme](theme.md) screen, separately from the library.
