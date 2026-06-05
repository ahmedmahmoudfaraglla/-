# Dev2Goo WordPress / Elementor Package

A complete premium-style package:

- **Theme** with Theme Options (Customizer) + a guided **Dev2Goo Setup** wizard.
- **Required plugin**: Elementor (installed automatically from WordPress.org).
- **Companion plugin** `dev2goo-site` (bundled inside the theme) that carries the
  design CSS and the one-click **demo importer**.

## Recommended flow (theme + guided import)

1. Go to **Appearance > Themes > Add New > Upload Theme**.
2. Upload `dev2goo-elementor.zip`, install it, and **Activate**.
3. You are sent to **Appearance > Dev2Goo Setup** (or use the admin notice).
4. **Step 1** — click *Install & Activate Elementor* (from WordPress.org).
5. **Step 2** — click *Install & Activate Dev2Goo Site* (bundled with the theme).
6. **Step 3** — click *Import Dev2Goo Demo*.

The import creates these Elementor-editable pages, sets **Home** as the front page,
and builds the primary/footer menus:

- Home
- Services
- Hosting & VPS
- Website Support
- About
- Contact

Edit logo, colors, and contact details under **Appearance > Customize > Dev2Goo
Theme Options**, and edit every page visually in **Elementor**.

## Alternative flow (plugin only, any theme)

If you prefer not to change your theme, upload only `dev2goo-site.zip`
(**Plugins > Add New > Upload Plugin**), activate it, open the **Dev2Goo Site**
admin menu, fill in your details, and click **Build / Import**. The design works
with any active theme because the pages use Elementor Canvas with the header and
footer baked in.

## Files

- `dev2goo-elementor/` — theme (Theme Options, header/footer, setup wizard, bundles the plugin).
- `dev2goo-site/` — companion plugin (design CSS + demo importer, Elementor canvas).
- `dist/dev2goo-elementor.zip` — uploadable theme (recommended starting point).
- `dist/dev2goo-site.zip` — uploadable companion plugin.
- `dist/dev2goo-wordpress-package.zip` — both archives bundled together.

## Notes

- The theme bundles the companion plugin at `lib/dev2goo-site.zip` and installs it
  for you during setup.
- Automatic plugin installation uses the WordPress filesystem. On hosts that require
  FTP credentials, use the manual upload fallback (the ZIPs in `dist/`).
- Pages still render even if Elementor is deactivated, because the markup is also
  stored in the page content.
