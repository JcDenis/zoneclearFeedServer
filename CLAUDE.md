# zoneclearFeedServer

Notes for AI assistants working on the Dotclear plugin zoneclearFeedServer.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Mix your blog with a feeds planet.

Admin page and configuration page. Public side: widgets, URL handlers.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/ActivityReportAction.php`: add feeds actions to the plugin activity report.
* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Combo.php`
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/FeedRow.php`
* `src/FeedsActions.php`: feeds list actions.
* `src/FeedsDefaultActions.php`: feeds list default actions.
* `src/FeedsList.php`: backend feeds list.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Install.php`: install and update, database structure (a change needs a migration here).
* `src/Manage.php`: admin page of the module.
* `src/ManageFeed.php`: admin page (feed).
* `src/ManageFeedVars.php`: admin page (feed vars).
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/PostsFilter.php`: backend feeds posts list filter.
* `src/PostsList.php`: backend feed posts lists.
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/RsExtPosts.php`: posts record extension to integrate feed info.
* `src/Settings.php`: module settings.
* `src/Template.php`: frontend template class.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/UninstallCleaner.php`: uninstall cleaner.
* `src/Upgrade.php`: upgrade class.
* `src/UrlHandler.php`: this adds public page that list feeds.
* `src/Widgets.php`: widgets definition and rendering.
* `src/ZoneclearFeedServer.php`: main class.
* `js/`: `feed.js`, `feeds.js`, `post.js`.
* `default-templates/`: public templates.
* Database table `zc_feed`.
* URL handlers: `zoneclearFeedsPage`.
* Widgets: `zcfssource`, `zcfsnumber`.
* Behaviors used: `publicBeforeDocumentV2`, `publicAfterDocumentV2`, `publicHeadContent`, `adminBeforePostDelete`, `initWidgets`, `UninstallerCleanersConstruct`, `adminDashboardFavoritesV2`, `adminColumnsListsV2`, `adminFiltersListsV3`, `adminPostListHeaderV2`, `adminPostListValueV2`, `adminPostHeaders`, `adminPostFormItems`, `coreBlogGetPosts`, `publicBreadcrumb`.

## Pitfalls

* Feeds are updated from the public side, in Ajax mode so that the visitor never waits (some feeds are very slow to parse). The cron script was removed on purpose: do not bring it back.
* Public user sessions belong to dedicated plugins (FrontendSession…): never open a PHP session on the public side, and think of the consequences for other plugins.
* Table structure changes need a migration in `src/Install.php` (version compare), and must work on MySQL, PostgreSQL and SQLite.

## Testing

* Check the admin page, the configuration page, the widgets (admin and public), the public URLs, the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
