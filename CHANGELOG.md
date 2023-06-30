 dev
- [ ] fix disallow self blog feeds
- [ ] add plugin muppet support (request Mathieu M.)
- [ ] add log for errors

2023.07.01
- require dotclear 2.26
- require php 8.1+
- fix php8.1 compliant
- duplicate settings to blog pref
- fix checkbox helpers

2023.05.13
- require dotclear 2.26
- require php 8.1+
- use define php_min
- use form helper
- use dotclear lock method
- add feed cursor helper
- fix action pages
- fix behaviors level
- fix null category
- fix translation
- fix type hint and nullsafe warnings

2023.05.08
- require dotclear 2.26
- require php 8.1+
- support plugin Uninstaller
- support plugin tweakurls 4.0
- use namespace

2022.12.11
- fix posts owner on feeds update
- fix category type
- fix permissions 
- enhance feed update
- use constant for table name
- use new behaviors names
- use dc methods in widget
- use abstract plugin name
- split file by class
- clean prepend file
- clean install file
- update settings ids to shorter ones
- update settings to json rather than serialise
- update translation

2022.11.26
- use SVG icon
- fix missing dcCore (resources)
- fix permissions check using contants
- fix update crach on unconfigured blog

2022.11.20
- fix compatibility with Dotclear 2.24 (required)
- fix feed update

2022.02.13
- Fix sqlStatement errors (dc 2.21)

2021.11.06
- add generic filters (dc 2.20)
- add user pref for columns and filters options (dc 2.20)
- fix redirections, page title, posts feed form
- update translation
- update to PSR12

2021.09.16
- fix Dotclear 2.19 compatibility
- fix php7.3+ php8.0 compatibility
- clean up code
- fix license
- remove all SoCialMe feature as this plugin is dead
- cometics fixes on admin pages

2015.07.19 - Pierre Van Glabeke
- modif lien vers tous les flux
- héritage thème mustek
- ajout breadcrumb
- localisation
- dc2.8 requis

2015.04.25 - Pierre Van Glabeke
- modif nom dans liste des plugins
- modif html5 pour validation

2015.01.13 - Pierre Van Glabeke
- Dotclear 2.7 requis
- Fin de ligne unix
- Ajout case hors ligne pour les widgets
- Jeux de templates modifiés (mustek/currywurst)
- Modifications locales

2013.11.18
- Require Dotclear 2.6
- New icons 'traviata' thx @franckpaul
- Use new Actions systems for feeds and posts

2013.07.12
- Added sort option on widget
- Fixed Dashboard counts
- Fixed Feed tags spliting
- Fixed typo

2013.07.02
- Required Dotclear 2.5
- Fixed admin pages titles and messages and typo
- Added Favorites icon
- Added new 'homeonly' option on widgets
- Fixed https protocol
- Added option to keep active empty feeds
- Added option to transform imported tags

1.3 - 2011.01.30
- Fixed install on nightly build
- Fixed bug on null blog (and settings)
- Added verbose on admin side
- Added behaviors to open to others plugins (ie messenger)
- Added support of plugin soCialMe (writer part)
- Removed messenger functions (as it's to another plugin to do this)

1.2 - 2010.09.11
- Added plugin tweakurls support (Thanks Mathieu M.)

1.1 - 2010.09.08
- Removed old Twitter functions
- Added StatusNet small functions (Identica)
- Required plugin Tac for Twitter ability

1.0 - 2010.06.27
- Switched to DC 2.2 (settings, tags)
- Fixed PHP 5.3 compatibility
- Added toogle menu and link to feed edition in admin post page
- Fixed redirection to original post
- Fixed typo

0.8 -2010.06.08
- Added auto tweet new post
- Added option to import tags from feed (or not)
- Fixed filters on admin list (closes #466)
- Enhanced lockUpdate() function

0.7.2 - 2010.05.25
- Fixed minor bugs
- Fixed DC 2.1.7

0.7.1 - 2010-05-01
- Fixed update order

0.7 - 2010.04.22
- Added icon on dashboard if some feeds are disabled
- Added public page of feed description (first step)
- Added update of existing entries
- Added settings to add "aftercontent" and ajax options
- Added uninstall features
- Fixed duplicate entry (I hope so) using php flock
- Fixed feeds actions on admin
- Fixed typo (closes #441)
- Fixed user settings (closes #442)

0.6 - 2010.04.11
- Added DC 2.2 compatibility (new setting)
- Added cron script to update feeds
- Fixed multiple bugs
- Changed admin interface

0.5.2 - 2010.04.05
- Added option to redirect url to local/original post
- Changed db fields names (fixed pgSQL compatibility)

0.5.1 - 2010.02.16
- Fixed php warning on admin post page

0.5 - 2010.02.08
- Added option to change update interval by group of feeds
- Fixed order by lower name
- Fixed HTML escape on content
- Fixed typo

0.4.3 - 2010.01.02
- Added option to update feeds from admin side
- Added behavior for more urls types for full content

0.4 - 2010.02.02
- Added option to change posts owner
- Added option to truncate content on some templates

0.3.1 - 2010.01.28
- Fixed bug with getURL on classic post

0.3 - 2010.01.27
- First lab release
- Fixed absolute URL
- Fixed unauthentication

0.2 - 2010.01.26
- First release

0.1 - 2010.01.25
- First test