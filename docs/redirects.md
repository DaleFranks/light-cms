# Redirects

A redirect sends visitors (and search engines) from an address that no longer has a page to a new one. They keep old links and search results working, for example after moving a site from another system.

## Adding a redirect

- **From**: the old address, such as `old-services` or `about_us.aspx`. To send a whole section, end it with `*`: `blog/*` covers every address starting with `blog/`. If the **To** address also ends with `*`, whatever followed is carried over, so `blog/*` to `/articles/*` sends `/blog/my-post` to `/articles/my-post`.
- **To**: where visitors go instead: an address on this site starting with `/`, such as `/services`, or a full web address.
- **Type**: **Permanent (301)** when the old address has moved for good, which tells search engines to move its ranking to the new address. **Temporary (302)** if it may come back.

Capital letters don't matter: `/About_Us.aspx` and `/about_us.aspx` are the same.

## How they work

- A redirect only applies where there's no published page, so it can never hide a live page.
- When a published page's address changes, a redirect from its old address is added automatically (marked "Added automatically"). Redirects that led to the old address are pointed straight at the new one, so visitors never go through a chain.
- **Uses** counts how often each redirect has been followed, and when it was last used, so you can see which old addresses still get visitors.
- **Try it** opens the old address in a new tab to check where it goes.
