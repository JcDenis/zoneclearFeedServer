/*global $, dotclear */
'use strict';

$(() => {
	/* toogle admin form sidebar */
	$('#zcfs h5').toggleWithLegend(
		$('#zcfs').children().not('h5'),
		{user_pref:'dcx_zcfs_admin_form_sidebar',legend_click:true}
	);
});