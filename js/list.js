/*global $, dotclear */
'use strict';

$(() => {
  $('.checkboxes-helpers').each(function(){dotclear.checkboxesHelpers(this);});
  dotclear.condSubmit('#form-feeds td input[type=checkbox]', '#form-feeds #feeds-action');
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #feed-action');
});