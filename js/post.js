/*global $, dotclear */
'use strict';

$(() => {
	/* toogle admin form sidebar */
	const title = document.querySelector('#zcfs h5');
	if (title) {
		dotclear.toggleWithLegend(title, document.querySelectorAll('#zcfs > :not(h5)'), {
			user_pref: 'dcx_zcfs_admin_form_sidebar',
			legend_click: true,
		});
	}
});