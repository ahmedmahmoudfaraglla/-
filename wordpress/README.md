# Dev2Goo WordPress / Elementor Package

The recommended way is the **single self-contained plugin**. It carries the full
design (header, footer, and all pages), works with **any active theme**, and the
pages are editable in **Elementor**.

## Option A (Recommended): One plugin only

1. (Optional but recommended) Install **Elementor** from **Plugins > Add New** so
   you can edit pages visually. The site renders correctly even without it.
2. Go to **Plugins > Add New > Upload Plugin**.
3. Upload `dev2goo-site.zip`, install it, and **Activate**.
4. Open the **Dev2Goo Site** menu in the WordPress admin sidebar.
5. Fill in your business details (phone, email, address, button), then **Save details**.
6. Click **Build / Import Dev2Goo Site**.

This creates the following pages as **Elementor Canvas** pages with the Dev2Goo
header and footer built in, and sets **Home** as the front page:

- Home
- Services
- Hosting & VPS
- Website Support
- About
- Contact

Because the design CSS is bundled inside the plugin and each page is a Canvas page,
the look does **not** depend on the active theme. Re-running the build updates the
same pages.

## Option B (Optional): Lightweight theme

`dev2goo-elementor` is a small optional theme that provides a matching header and
footer at the theme level (for users who prefer the design to come from the theme
instead of Canvas pages). It is **not required** when using Option A.

## Files

- `dev2goo-site/` - the all-in-one plugin (recommended).
- `dev2goo-elementor/` - optional lightweight theme.
- `dist/dev2goo-site.zip` - uploadable plugin (recommended).
- `dist/dev2goo-elementor.zip` - uploadable optional theme.
- `dist/dev2goo-wordpress-package.zip` - everything bundled together.

## Notes

- The plugin works with any theme; the optional theme is only for theme-level header/footer.
- All pages are Elementor-editable (HTML widget inside an Elementor Canvas page).
- Even without Elementor active, pages still render because the markup is also stored
  in the page content.
