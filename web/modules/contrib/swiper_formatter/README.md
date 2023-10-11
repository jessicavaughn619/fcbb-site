## Swiper formatter

### General Information

Provides Drupal integration with the one of the most modern swiping/sliding libraries [Swiper.js](https://swiperjs.com/).
Swiper.js is mobile first, layout and gestures wise, it provides a huge amount
of options for designing your own widget.

This module is not meant to be yet another images slider, or such.
It is rather a swiping UI widget for almost any kind of content,
and in both directions horizontal and vertical - markup/text fields,
media fields (i.e. video), content entity references fields,
Views content in the classic way, some type of fields within Views etc.

### Features

- **Integration of the latest version of Swiper.js**, currently that is 8.x.x.

- **[Swiper.js' features]**(https://swiperjs.com/demos) is rather a huge list,
  a solid group of those are included and mostly tested. Note that combinations
  of some particular ones are not meant by [Swiper.js design](https://swiperjs.com/swiper-api)

- **[Configuration Entity]**(https://www.drupal.org/node/1809494) implementation
  which, contrary to already existing contributed Drupal modules for Swiper.js,
  brings somewhat different concept. Those entities are unique sets of
  configurations (which become parameters and modules for
  Swiper.js definition in front-end) and
  there can be unlimited number of these, each ready as a sort of pre-defined
  template for usage on Field formatter settings or in the Views.

- **Views'style plugin** "Swiper formatter" which will turn any View
  into a swiping game (see more below about advanced usage).

- **Swiper markup** field formatter plugin for text field types - renders slides
  from any kind of text/markup, coming from text fields,
  including rich text fields.

- **Swiper images** field formatter plugin for image type of fields.
  It extends on a current image formatter and respects all of parent settings,
  such as Image style and Image link.

- **Swiper entity** field formatter plugin, currently basic tested
  on Media entity reference field as well as Content entity reference field.

- **Each Swiper.js instance is fully unique** so we can have as many,
  and as different or the same, in parallel on the same page.

- **Captions** for slides can be any multiple values fields of text type
  belonging to a same entity. For images this can be Alt or Title
  sub-fields as well.

- **Swiper.js modules integrated:**
  Pagination, Navigation, Autoplay, Lazy Loading (for images)

- **PHP 8.x** version compatibility.


### Swiper.js documentation
Please check out [Swiper.js extensive API](https://swiperjs.com/swiper-api) for more in depth documentation.


### Installation

1. Fetch module via Composer
  `composer require drupal/swiper_formatter`
2. Enable module either via Drupal UI or with Drush
  `drush en swiper_formatter`


### Use

1. Make sure "Administer Swiper formatter" permission is set for operating user.

2. Visit "Configuration > Content authoring > Swipers"
   and create your first swiper template.
   A BIG note here: By default Swiper.js is defined as and external library
   in "swiper_formatter.libraries.yml" file. Tested as such with default theme
   Bartik and it operates just fine, however it is possible that under
   some context, some themes and libraries, or some advanced caching mechanisms
   it turns less stable. In such case, to try to resolve select
   "Local" or "Local minified" for "Swiper library source"
   on each swiper entity form.

3. Configure Field formatter or a View style
   a. For a field, on a Node for instance, go to "Manage display"
      for the content type in question and set appropriate formatter,
      either for image type of field or text type of field. Or both!
   b. For a view style - either on existing or a new view display
      set "Swiper formatter" for a format
      and assign the rest of the settings found there.  


### Advanced usage in Views

This module provides possibility for at least
three different ways for using with Views:

1. Most common usage, just any regular View display
   having "Swiper formatter" set for "Format" whereas both
   fields and content with a view mode ("Show: Fields" and "Show: Content")
   will work. Everything that is one view result
   or #row will become one slide.

2. In case of Content formatted view (with usage of View mode)
   field enabled in such, with any swiper formatter should work,
   being part of the result.

3. It is possible to use a single field, say multiple values field
   (images or text) belonging to multiple entities returned as a
   view result to render in Swiper in sequential order - i.e.
   Node[delta=0]
     Image[delta = 0]
     Image[delta = 1]
     Image[delta = 2]
   Node[delta=1]
     Image[delta = 0]
     Image[delta = 1].
   For this you'd need to have only one multiple values field
   (or may be maximum two fields in a View if setting one for Captions)
   which would have"Display all values in the same row" turned of within
   "Multiple field settings" for that field.


#### TODO

- Provide support for Paragraphs
- Develop CKEditor 5 plugin


#### Authors/Credits

* [nk_](https://www.drupal.org/u/nk_)
