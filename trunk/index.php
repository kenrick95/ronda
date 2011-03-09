<?php
/**
 * 2011-03-07 10:45
 */
$ronda = new ronda();
$raws = $ronda->rc($_GET);
if ($raws)
{
	$rcs = array();
	if ($raws2 = $raws['query']['recentchanges'])
	{
		// get unique page id
		foreach ($raws2 as $raw)
		{
			$key = $raw['pageid'];
			if (!array_key_exists($key, $rcs))
			{
				$rcs[$key]['pageid'] = $raw['pageid'];
				$rcs[$key]['revid'] = $raw['revid'];
				$rcs[$key]['old_revid'] = $raw['old_revid'];
				if (array_key_exists('minor', $raw)) $rcs[$key]['minor'] = $raw['minor'];
				$rcs[$key]['timestamp'] = $raw['timestamp'];
				$rcs[$key]['title'] = $raw['title'];
				$rcs[$key]['user'] = $raw['user'];
				$rcs[$key]['type'] = $raw['type'];
				$rcs[$key]['newlen'] = $raw['newlen'];
				$rcs[$key]['oldlen'] = $raw['oldlen'];
				$rcs[$key]['count'] = 1;
				$rcs[$key]['users'][$raw['user']]['count'] = 1;
			}
			else
			{
				if (array_key_exists('new', $raw)) $rcs[$key]['type'] = 'new';
				$rcs[$key]['old_revid'] = $raw['old_revid'];
				$rcs[$key]['count']++;
				$rcs[$key]['users'][$raw['user']]['count']++;
				$rcs[$key]['oldlen'] = $raw['oldlen'];
			}
			$rcs[$key]['changes'][] = $raw;
			$rcs[$key]['ns'] = $raw['ns'];
			if (array_key_exists('anon', $raw))
			{
				$rcs[$key]['anon'] = 'yes';
				$rcs[$key]['users'][$raw['user']]['anon'] = true;
			}
		}
		// write
		$trusted = array(
			'Meursault2004',
			'Hayabusa future',
			'Stephensuleeman',
			'IvanLanin',
			'Ciko',
			'Wic2020',
			'Rintojiang',
			'REX',
			'Kembangraps',
			'Gombang',
			'Andri.h',
			'Tjmoel',
			'Mimihitam',
			'BlackKnight',

			'Alagos',
			'Albertus Aditya',
			'Bennylin',
			'Evremonde',
			'Ezagren',
			'Farras',
			'Kenrick95',
			'Kia 80',
			'M. Adiputra',
			'Maqi',
			'NoiX180',
			'Reindra',
			'StefanusRA',
			'Tatasport',
			'Wiendietry',
			'Willy2000',
		);
		$ret .= '<table class="rc">';
		foreach ($rcs as $rci)
		{
			$rc = $rci;
			$users = '';
			$time = strtotime($rc['timestamp']);

			$rc['difflen'] = intval($rc['newlen']) - intval($rc['oldlen']);
			$rc['difflen'] = ($rc['difflen'] > 0 ? '+' : '') . $rc['difflen'];
			$rc['diffclass'] = intval($rc['difflen']) >= 0 ? 'size-pos' : 'size-neg';
			if (intval($rc['difflen']) == 0) $rc['diffclass'] = 'size-null';
			if (abs(intval($rc['difflen'])) >= 500) $rc['diffclass'] .= ' size-large';

			foreach ($rc['users'] as $user_id => $user)
			{
				$class = $user['anon'] ? 'user-anon' : 'user-login';
				if (!$user['anon'] && in_array($user_id, $trusted)) $class = 'user-trusted';
				$users .= $users ? '; ' : '';
				$users .= sprintf('<a href="http://id.wikipedia.org/wiki/Istimewa:Kontribusi_pengguna/%1$s" class="%2$s">%1$s</a>', $user_id, $class);
				$users .= $user['count'] > 1 ? ' (' . $user['count'] . 'x)' : '';
			}
			$class = ($rc['anon'] == 'yes') ? 'anon' : '';
			$cur_date = date('d M Y', $time);
			$url = sprintf('http://id.wikipedia.org/w/index.php?diff=%1$s&oldid=%2$s', $rc['revid'], $rc['old_revid']);
			if ($ronda->diff_only) $url .= '&diffonly=1';

			if ($cur_date != $last_date)
			{
				$ret .= sprintf('<tr><td colspan="6" class="date">%1$s</td></tr>', $cur_date);
			}
			$ret .= sprintf('<tr class="%1$s" valign="top">', $class);
			$ret .= sprintf('<td width="1" class="ns-%2$s">%1$s</td>', '&nbsp;', $rc['ns']);
			$ret .= sprintf('<td>%1$s</td>', ($rc['type'] == 'new' ? 'B' : ''));
			//$ret .= sprintf('<td>%1$s</td>', ($rc['type'] == 'new' ? 'B' : '&nbsp;&nbsp;') . (array_key_exists('minor', $rc) ? 'k' : '&nbsp;&nbsp;'), $url);
			$ret .= sprintf('<td>%1$s</td>', date('H.i', $time));
			$ret .= sprintf('<td><a href="%2$s">%1$s</a>%3$s</td>', $rc['title'], $url,
				($rc['count'] > 1 ? ' (' . $rc['count'] . 'x)' : '')
			);
			$ret .= sprintf('<td align="center" nowrap class="%2$s">%1$s</td>', $rc['difflen'], $rc['diffclass']);
			$ret .= sprintf('<td>%1$s</td>', $users);
			$ret .= sprintf('<td class="changes">%1$s</td>', format_summary($rc['changes']));
			$ret .= '</tr>';

			$last_date = $cur_date;
		}
		$ret .= '</table>';
	}
}
$TITLE = 'Ronda';
$CONTENT .= sprintf('<h1>%1$s</h1>', $TITLE);
$CONTENT .= $ronda->search;
$CONTENT .= $ret;

function format_summary($changes)
{
	$max = 50;
	$ret = count($changes) == 1 ? strip_tags($changes[0]['parsedcomment']) : '';
	if (strlen($ret) > $max) $ret = substr($ret, 0, $max) . ' ...';
	return($ret);
}

/**
 * 2011-03-08 11:02
 */
class ronda
{
	var $user_agent = 'Ronda - A custom recent changes for Indonesian Wikipedia - User:IvanLanin';
	var $default_limit = 500;
	var $default_ns = '0|1|2|4|5|6|7|8|9|10|11|12|13|14|15|100|101';
	var $max_limit = 500;
	var $min_limit = 1;
	var $search;

	/**
	 */
	function rc($get)
	{
		// param
		$rc_exclude_user = trim($get['exclude_user']);
		$rc_type = $get['new'] ? 'new' : 'new|edit';
		$rc_anon = $get['anon'] ? '|anon' : '';
		$this->diff_only = $get['diff'] == 1 ? 1 : 0;

		// limit
		$rc_limit = intval($get['limit']);
		if (!$rc_limit) $rc_limit = $this->default_limit;
		if ($rc_limit > $this->max_limit) $rc_limit = $this->max_limit;
		if ($rc_limit < $this->min_limit) $rc_limit = $this->min_limit;

		// namespace
		$nss = array(
			0 => 'Artikel',
			2 => 'Pengguna',
			4 => 'Wikipedia',
			6 => 'Berkas',
			8 => 'MediaWiki',
			10 => 'Templat',
			12 => 'Bantuan',
			14 => 'Kategori',
			100 => 'Portal',
			1 => 'Pembicaraan Artikel',
			3 => 'Pembicaraan Pengguna',
			5 => 'Pembicaraan Wikipedia',
			7 => 'Pembicaraan Berkas',
			9 => 'Pembicaraan MediaWiki',
			11 => 'Pembicaraan Templat',
			13 => 'Pembicaraan Bantuan',
			15 => 'Pembicaraan Kategori',
			101 => 'Pembicaraan Portal',
		);
		$ns = $get['ns'];
		if (is_array($ns))
		{
			foreach ($ns as $ni)
			{
				$ni = trim($ni);
				if (array_key_exists($ni, $nss))
				{
					$rc_ns .= ($rc_ns != '' ? '|' : '') . $ni;
				}
			}
		}
		if (!$rc_ns) $rc_ns = $this->default_ns;
		$rc_ns_array = explode('|', $rc_ns);

		// search form
		$search .= '<form id="search" name="search" method="get" action="./">';
		$search .= sprintf('Jumlah entri: <input type="text" name="limit" size="5" value="%1$s" /> ', $rc_limit);
		$search .= sprintf('Kecualikan pengguna: <input type="text" name="exclude_user" size="15" value="%1$s" /> ', $rc_exclude_user);
		$search .= '<br />Pilihan: ';
		$search .= sprintf('<input type="checkbox" name="new" value="1" %1$s/>Hanya baru ',
			$rc_type == 'new' ? 'checked="checked" ' : '');
		$search .= sprintf('<input type="checkbox" name="anon" value="1" %1$s/>Hanya anon ',
			$rc_anon ? 'checked="checked" ' : '');
		$search .= sprintf('<input type="checkbox" name="diff" value="1" %1$s/>Hanya perbedaan ',
			$this->diff_only ? 'checked="checked" ' : '');
		$search .= '<br />Ruang nama:';
		$search .= '<table class="search"><tr>';
		foreach ($nss as $key => $val)
		{
			if ($key == 1) $search .= '</tr><tr>';
			$val = str_replace('Pembicaraan ', 'P.', $val);
			$search .= sprintf(
				'<td><input type="checkbox" name="ns[]" value="%1$s" %3$s/>%2$s</td>',
				$key, $val,
				in_array(intval($key), $rc_ns_array) ? 'checked="checked" ' : ''
			);
		}
		$search .= '</tr></table>';
		$search .= '<input type="submit" value="Cari perubahan" />';
		$search .= '<input type="button" value="Setelan bawaan" onclick="location.href = \'./\'" />';
		$search .= '</form>';
		$this->search = $search;

		// curl
		$base = 'http://id.wikipedia.org/w/api.php?';
		$param = 'action=query' .
			'&list=recentchanges' .
			'&rctype=%1$s' .
			'&rclimit=%2$s' .
			'&rcnamespace=%3$s' .
			'&rcshow=!bot%4$s' .
			'%5$s' .
			'&rcprop=title|timestamp|user|ids|flags|sizes|parsedcomment' .
			'&format=json';
		$param = sprintf($param, $rc_type, $rc_limit, $rc_ns, $rc_anon,
			$rc_exclude_user ? '&rcexcludeuser=' . urlencode($rc_exclude_user) : ''
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $base . $param);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		$output = curl_exec($ch);
		$ret = json_decode($output, true);
		curl_close($ch);
		return($ret);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo($TITLE); ?></title>
<?php echo($HEADER); ?>
<style>
a { text-decoration: none; }
a:hover { text-decoration: underline; }
table.rc { border-collapse: collapse; width: 100%;  }
table.rc td { padding: 2px 5px; border-bottom: 1px solid #FFF; }
tr.anon td { background: #eee; }
form { margin-bottom: 20px; padding: 5px; background: #eee; }

.date { font-weight: bold; font-size: 120%; padding: 10px 0px; }
.changes { font-style: italic; }

.user-trusted { color: #999999; }
.user-login { color: #330099; }
.user-anon { color: #CC0000; }

.size-neg { color:#FF2050; }
.size-pos { color:#00B000; }
.size-null { color:#999; }
.size-large { font-weight: bold; }

.ns-0, .ns-1 { background: #C0C0C0 !important; } /* Artikel */
.ns-2, .ns-3 { background: #00FFFF !important; } /* Pengguna */
.ns-4, .ns-5 { background: #800080 !important; } /* Wikipedia */
.ns-6, .ns-7 { background: #800000 !important; } /* Berkas */
.ns-8, .ns-9 { background: #FFFF00 !important; } /* MediaWiki */
.ns-10, .ns-11 { background: #808000 !important; } /* Templat */
.ns-12, .ns-13 { background: #FF00FF !important; } /* Bantuan */
.ns-14, .ns-15 { background: #008080 !important; } /* Kategori */
.ns-100, .ns-101 { background: #008000 !important; } /* Portal */
</style>
</head>
<body>
<?php echo($CONTENT); ?>
<!--
LOG PERUBAHAN:

2011-03-09:

* INFO: Pencarian pengguna peka kapitalisasi
* INFO: Jumlah perubahan maksimum yang bisa diambil tanpa masuk log = 500
* BARU: Pembedaan warna pengguna tepercaya, terdaftar, dan anon
* BARU: Dua baris pilihan ruang nama
* INFO: Pranala ke WP:PT tidak dibuat karena bisa diakses setelah mengeklik salah satu pranala
* BARU: Kolom penanda warna untuk masing-masing ruang nama
-->
</body>
</html>