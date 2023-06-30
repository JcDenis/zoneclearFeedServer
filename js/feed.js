/*global $, dotclear */
'use strict';

$(() => {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-entries td input[type=checkbox]', '#form-entries #feed-action');
  });
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #feed-action');
});