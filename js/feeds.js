/*global $, dotclear */
'use strict';

$(() => {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-feeds td input[type=checkbox]', '#form-feeds #feeds-action');
  });
  dotclear.condSubmit('#form-feeds td input[type=checkbox]', '#form-feeds #feeds-action');
});