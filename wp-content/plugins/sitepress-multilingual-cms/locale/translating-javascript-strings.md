# 1. Preparation

1. Run `npm install`.
2. For good measure, in the `package.json`, run this check:
    1. Look for occurrences of `GETTEXT_DOMAIN=`.
    2. Check that, after the "=" there is the current plugin's text domain (e.g. `sitepress` for wpml-core). If not, fix it.
3. Check that the `.gitignore` file of the project contains this line: `/locale/jed/**/*.po`.

Note that, since WPML-Core embeds Translation Management as a Composer plugin, in `package.json`, you must have two occurrences of `GETTEXT_DOMAIN`: one for Core and another for TM.

# 2. Generating or updating the POT files

Run `npm run strings:update-pot`.

This command will generate or update the POT files for each JS app in `/locale/jed/pot`.

You can send these files for translation.

# 3. Updating the translations

1. Save the completed translations in `/locale/jed/po`.
2. Run `npm run strings:jed`  to generate the JED files in `/locale/jed`.
3. Test the translations.
4. Commit the changes.

# 4. Additional notes

When committing the changes, you should only commit the POT and JED files (respectively, ``/locale/jed/pot/*.pot` and `/locale/jed/*.json`).
