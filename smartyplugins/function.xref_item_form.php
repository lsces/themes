<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {xref_item_form} function plugin
 *
 * Renders the input fields for one xref item on a content object - the item's own fields template,
 * with no heading, form tag or buttons - so a page (the add page above all) can show the right
 * fields for each item type without knowing which template that is. The template comes from
 * LibertyContent::getXrefFieldsTemplate(); an item whose template has no fields template yet is
 * shown with the plain text fields rather than a whole page nested inside a form. It is fetched in
 * its own scope, so what it assigns cannot leak back into the calling page.
 *
 * Type:     function
 * Name:     xref_item_form
 * Input:
 *           - content   (required) - the LibertyContent the row belongs to
 *           - item      (required) - the liberty_xref_item code
 *           - template  (optional) - the item's template name; omit for the plain text fields
 *           - row       (optional) - an existing row to show; omit for a blank row (add)
 *           - xid       (optional) - prefix for the element ids, so several items can share a page
 */
function smarty_function_xref_item_form( $params, $template ) {
	$content = $params['content'] ?? null;
	$item    = (string)( $params['item'] ?? '' );
	if( !is_object( $content ) || $item === '' ) {
		return '';
	}

	// A blank row, carrying what a loaded one would: the item's definition (title, template, field
	// hints) and the default dates a new row starts with. A row passed in (an edit, or a failed add
	// shown again) is laid over those, so it still gets the definition's hints.
	$row = array_merge(
		[
			'xref_id'    => '',
			'content_id' => $content->mContentId,
			'item'       => $item,
			'xkey'       => '',
			'xkey_ext'   => '',
			'data'       => '',
			'xref'       => '',
			'xorder'     => 0,
			'ignore_start_date' => 'n',
			'start_date'        => time(),
			'ignore_end_date'   => 'y',
		],
		(array)$content->getXrefItemDefinition( $item ),
		(array)( $params['row'] ?? [] )
	);
	// The template named by the caller is the one being shown, so the content class's per-row hook
	// (below) sees it even for an item whose definition is not in the table.
	$name = trim( (string)( $params['template'] ?? $row['template'] ?? '' ) );
	if( $name !== '' ) {
		$row['template'] = $name;
	}
	// The edit path's per-row hook: computed display fields (linked title, lookup, pack size, ...).
	$content->enrichXrefDisplay( $row );

	$file = $content->getXrefFieldsTemplate( $name ) ?? $content->getXrefFieldsTemplate( 'text' );

	// Parent scope = the calling template, so $gContent and friends are visible; the child's own
	// assignments stay in the child.
	$tpl = $template->getSmarty()->createTemplate( $file, null, null, $template );
	$tpl->assign( 'xrefInfo', $row );
	$tpl->assign( 'xid', (string)( $params['xid'] ?? '' ) );
	return $tpl->fetch();
}
