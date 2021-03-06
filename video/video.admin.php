<?php defined('COT_CODE') or die('Wrong URL');
/* ====================
[BEGIN_COT_EXT]
Hooks=admin
[END_COT_EXT]
==================== */

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('video', 'a');
cot_block($usr['isadmin']);

$c = cot_import('c', 'G', 'TXT');

require_once cot_incfile('video', 'module');

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if ($a == 'add')
	{
		$vid = video_import();
		if (video_validate($vid))
		{
			$id = video_add($vid);
			cot_message($L['Added'] . ' #' . $id);
		}
	}
	elseif ($a == 'update')
	{
		$id = cot_import('id', 'G', 'INT');
		$vid = $db->query("SELECT * FROM $db_videos
				WHERE vid_id = ?", $id)
				->fetch();

		if ($vid)
		{
			$vid = video_import('POST', $vid, 'rvid' . $id);
			if (video_validate($vid) && video_update($id, $vid))
			{
				cot_message($L['Updated'] . ' #' . $id);
			}
		}
	}

	// Return to the main page and show messages
	cot_redirect(cot_url('admin', 'm=video', '', true));
}
elseif ($a == 'delete')
{
	$id = cot_import('id', 'G', 'INT');
	if (video_delete($id))
	{
		cot_message($L['Deleted'] . ' #' . $id);
	}
	cot_redirect(cot_url('admin', 'm=video', '', true));
}

$adminpath[] = array(cot_url('admin', 'm=extensions'), $L['Extensions']);
$adminpath[] = array(cot_url('admin', 'm=extensions&a=details&mod='.$m), $L['vid_title']);
$adminpath[] = array(cot_url('admin', 'm='.$m), $L['Administration']);

$t = new XTemplate(cot_tplfile('video.admin', 'module', true));

cot_display_messages($t);

list($pg, $d, $durl) = cot_import_pagenav('d', $cfg['maxrowsperpage']);

// Display the main page
$video_where_sql = !empty($c) ? "WHERE vid_cat=".$db->quote($c) : '';

$res = $db->query("SELECT * FROM $db_videos $video_where_sql ORDER BY vid_order, vid_id DESC LIMIT $d, ".(int)$cfg['maxrowsperpage']);

foreach ($res->fetchAll() as $row)
{
	$t->assign(video_tplform($row, 'VIDEO_ADMIN_ROW_', 'rvid' . $row['vid_id']));
	$t->assign(array(
		'VIDEO_ADMIN_ROW_UPDATE_URL' => cot_url('admin', 'm=video&a=update&id=' . $row['vid_id']),
		'VIDEO_ADMIN_ROW_DELETE_URL' => cot_confirm_url(cot_url('admin', 'm=video&a=delete&id=' . $row['vid_id']))
	));
	$t->parse('MAIN.VIDEO_ADMIN_ROW');
}

$t->assign(array(
	'VIDEO_ADMIN_URL' => cot_url('admin', 'm=video'),
	'VIDEO_ADMIN_ADD_URL' => cot_url('admin', 'm=video&a=add')
));

$t->assign(video_tplform(array(), 'VIDEO_ADMIN_ADD_'));

$totalitems = $db->query("SELECT COUNT(*) FROM $db_videos $video_where_sql")->fetchColumn();

$pagenav = cot_pagenav('admin', 'm=video&c='.$c, $d, $totalitems, $cfg['maxrowsperpage'], 'd');

$video_empty_structure = array(
	'tpath' => '---',
);
$usr['auth']['video'][''] = 255;
$structure['video'] = array_merge(array('' => $video_empty_structure), $structure['video']);

$t->assign(array(
	'VIDEO_ADMIN_FILTER_ACTION' => cot_url('admin'),
	'VIDEO_ADMIN_FILTER_CAT'	=> cot_selectbox_structure('video', $c, 'c'),
	'VIDEO_ADMIN_PAGENAV'		=> $pagenav['main'],
	'VIDEO_ADMIN_PAGEPREV'		=> $pagenav['prev'],
	'VIDEO_ADMIN_PAGENEXT'		=> $pagenav['next'],
));

unset($structure['video'][''], $usr['auth']['video']['']);

$t->parse();
$adminmain = $t->text('MAIN');
