# Omythic Child Theme
The Omythic Child Theme is the basis of all Omythic custom websites and must be used in conjunction with the [Omythic Framework Theme](https://github.com/makespaceweb/makespace-framework).

## How To Use

* If using the [Makespace WordPress Installer](https://github.com/makespaceweb/wordpress-installer), this child theme will automatically be added to your project.
* If using this theme manually, you will need to port over the `bower.json`, `gulpfile.js` and `package.json` files from the [Makespace WordPress Installer](https://github.com/makespaceweb/wordpress-installer) and change them to suit your project

## Code Compliance
Your code should conform to the [WordPress coding standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) but also adhere to our theme building best practices. The following is not an exhaustive list:

* Each page layout should have its own *Page Template* and a corresponding SCSS file in the `src/scss/pages` folder. We started you with `page.php` and `page_contact.php` as example and guides.
* Each post type should have its own `single-{post_type}.php` file and a corresponding SCSS file in the `src/scss/pages` folder if they will be viewed as individual pages.
* If custom post types will have paginated archives, you should create `archive-{post_type}.php` (with a SCSS file in `src/scss/pages`).
* WordPress administrative users **must be able to edit all content and images** through the WordPress dashboard, and without having to write any HTML code. This does not apply to general font selection and colors, but does apply to all content.

## Version Control Best Practices

* You must include a copy of your database with each major or end-of-day commit and push
* The database name should be `[databasename].sql.gz` where [databasename] is the name of the database itself
* You should only edit the `.gitignore` if you introduce:
  * another type of package manager and need to ignore its output (eg, if you bring in Composer and want to ignore the vendor packages it installs)
  * a caching plugin and you want to ignore the cached files it creates (you should)

## Issues or Problems
If you run into any problems or issues with (or see opportunities for optimizations of) the code in this child theme, you should [post them in the issues section of this repo](https://github.com/makespaceweb/makespace-child/issues).