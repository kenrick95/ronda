<?php
/**
 * mods:
 * rc: recent changes (perubahan terbaru)
 * pr: pending revision (revisi tunda)
 *
 * 2011-03-08 11:02
 */
class ronda
{
	var $title;
	var $mod;
	var $mods = array(
		'rc' => array(
			'title' => 'Perubahan terbaru',
		),
		'pr' => array(
			'title' => 'Suntingan tunda',
		),
	);
	var $user_agent = 'Ronda - http://code.google.com/p/ronda';
	var $default_limit = 500;
	var $default_ns = '0|1|2|4|5|6|7|8|9|10|11|12|13|14|15|100|101';
	var $max_limit = 500;
	var $min_limit = 1;
	var $search; // html for search
	var $menu; // html for menu
	var $data;
	var $anon_only = false;
	var $diff_only = false;
	var $namespaces = array(
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

	/**
	 */
	function process($get)
	{
		$this->mod = array_key_exists($get['mod'], $this->mods) ? $get['mod'] : 'rc';
		$this->title = 'Ronda: ' . $this->mods[$this->mod]['title'];
		foreach ($this->mods as $key => $val)
		{
			$menu .= $menu ? ' | ' : '';
			$menu .= sprintf('<a href="./?mod=%2$s">%1$s</a>', $val['title'], $key);
		}
		$this->menu = $menu;
		switch ($this->mod)
		{
			case 'rc':
				$this->process_rc($get);
				break;
			case 'pr':
				$this->process_pr($get);
				break;
		}

	}

	/**
	 */
	function html()
	{
		switch ($this->mod)
		{
			case 'rc':
				$content = $this->html_rc();
				break;
			case 'pr':
				$content = $this->html_pr();
				break;
		}
		$ret .= sprintf('<div id="menu">%1$s</div>', $this->menu);
		$ret .= sprintf('<h1>%1$s</h1>', $this->title);
		$ret .= $this->search;
		$ret .= $content;
		return($ret);
	}

	/**
	 */
	function process_rc($get)
	{
		// param
		$rc_exclude_user = trim($get['exclude_user']);
		$rc_type = $get['new'] ? 'new' : 'new|edit';
		$rc_anon = $get['anon'] ? '|anon' : '';
		$this->anon_only = ($get['anon'] != '');
		$this->diff_only = ($get['diff'] == 1 ? 1 : 0);

		// limit
		$rc_limit = intval($get['limit']);
		if (!$rc_limit) $rc_limit = $this->default_limit;
		if ($rc_limit > $this->max_limit) $rc_limit = $this->max_limit;
		if ($rc_limit < $this->min_limit) $rc_limit = $this->min_limit;

		$ns = $get['ns'];
		if (is_array($ns))
		{
			foreach ($ns as $ni)
			{
				$ni = trim($ni);
				if (array_key_exists($ni, $this->namespaces))
				{
					$rc_ns .= ($rc_ns != '' ? '|' : '') . $ni;
				}
			}
		}
		if ($rc_ns == '')
		{
			$rc_ns = $this->default_ns;
			$get['ns_select'] = 1;
		}
		$rc_ns_array = explode('|', $rc_ns);

		$ns_select = array(
			1 => 'Bawaan',
			2 => 'Semua',
			3 => 'Hanya artikel',
			4 => 'Tanpa pembicaraan',
			5 => 'Hanya pembicaraan',
		);

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
		$search .= '<br />Ruang nama: ';
		$search .= '<select name="ns_select" id="ns_select" onChange="select_ns(this.form)">';
		$search .= '<option value=""></option>';
		foreach ($ns_select as $key => $val)
		{
			$search .= sprintf('<option value="%1$s"%3$s>%2$s</option>',
				$key, $val,
				$get['ns_select'] == $key ? 'selected' : ''
			);
		}
		$search .= '</select>';
		$search .= '<table class="search"><tr>';
		foreach ($this->namespaces as $key => $val)
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
		$params = array(
			'action'      => 'query',
			'list'        => 'recentchanges',
			'rctype'      => $rc_type,
			'rclimit'     => $rc_limit,
			'rcnamespace' => $rc_ns,
			'rcprop'      => 'title|timestamp|user|ids|flags|sizes|parsedcomment|redirect',
			'rcshow'      => '!bot' . $rc_anon,
		);
		if ($rc_exclude_user)
		{
			$params['rcexcludeuser'] = urlencode($rc_exclude_user);
		}
		$this->data = $this->curl($params);
	}

	/**
	 */
	function html_rc()
	{
		$raws = $this->data;
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
					if ($this->check_revert($raw['parsedcomment'])) $rcs[$key]['revert'] = true;
					if (array_key_exists('redirect', $raw)) $rcs[$key]['redirect'] = true;
					$rcs[$key]['changes'][] = $raw;
					$rcs[$key]['ns'] = $raw['ns'];
					if (array_key_exists('anon', $raw))
					{
						$rcs[$key]['anon'] = 'yes';
						$rcs[$key]['users'][$raw['user']]['anon'] = true;
					}
				}
				// write
				$trusted = $this->trusted_users();
				$ret .= '<table class="data">';
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
					$class = ($rc['anon'] == 'yes' && !$this->anon_only) ? 'anon' : '';
					$cur_date = date('d M Y', $time);
					$url = sprintf('http://id.wikipedia.org/w/index.php?diff=%1$s&oldid=%2$s', $rc['revid'], $rc['old_revid']);
					if ($this->diff_only) $url .= '&diffonly=1';

					if ($cur_date != $last_date)
					{
						$ret .= sprintf('<tr><td colspan="6" class="date">%1$s</td></tr>', $cur_date);
					}
					$ret .= sprintf('<tr class="%1$s" valign="top">', $class);
					$ret .= sprintf('<td width="1" class="ns-%2$s">%1$s</td>', '&nbsp;', $rc['ns']);
					$ret .= sprintf('<td>%1$s</td>', ($rc['type'] == 'new' ? 'B' : ''));
					$ret .= sprintf('<td>%1$s</td>', date('H.i', $time));
					$ret .= sprintf('<td class="%4$s"><a href="%2$s">%1$s</a>%3$s</td>', $rc['title'], $url,
						($rc['count'] > 1 ? ' (' . $rc['count'] . 'x)' : ''),
						($rc['redirect'] ? 'redirect ' : '') .
							($rc['revert'] ? 'revert ' : '') .
							($rc['type'] == 'new' ? 'new ' : '')
					);
					$ret .= sprintf('<td align="center" nowrap class="%2$s">%1$s</td>', $rc['difflen'], $rc['diffclass']);
					$ret .= sprintf('<td>%1$s</td>', $users);
					$ret .= sprintf('<td class="changes">%1$s</td>', $this->format_summary($rc['changes']));
					$ret .= '</tr>';

					$last_date = $cur_date;
				}
				$ret .= '</table>';
			}
		}
		return($ret);
	}

	/**
	 * http://id.wikipedia.org/wiki/Istimewa:Halaman_tertinjau_usang
	 * http://id.wikipedia.org/wiki/Istimewa:Statistik_validasi
	 */
	function process_pr()
	{
		$params = array(
			'action'      => 'query',
			'list'        => 'oldreviewedpages',
			'ordir'       => 'older',
			'orlimit'     => $this->default_limit,
		);
		$this->data = $this->curl($params);
	}

	/**
	 * http://id.wikipedia.org/w/index.php?diff=cur&oldid=4111616
	 */
	function html_pr()
	{
		$base = 'http://id.wikipedia.org/w/index.php?diff=cur&oldid=%1$s&diffonly=1';
		if ($rows = $this->data['query']['oldreviewedpages'])
		{
			$ret .= '<table class="data">';
			foreach ($rows as $row)
			{
				$url = sprintf($base, $row['stable_revid']);
				$time = strtotime($row['pending_since']);
				$cur_date = date('d M Y', $time);

				if ($cur_date != $last_date)
				{
					$ret .= sprintf('<tr><td colspan="2" class="date">' .
						'%1$s</td></tr>', $cur_date);
				}
				$ret .= '<tr>';
				$ret .= sprintf('<td width="1">%1$s</td>', date('H.i', $time));
				$ret .= sprintf(
					'<td><a href="%2$s" class="%4$s">%1$s</a> . . %3$s</td>',
					$row['title'], $url,
					$this->format_diff($row['diff_size']),
					$row['under_review'] ? 'revert' : ''
				);
				$ret .= '</tr>';
				$last_date = $cur_date;
			}
			$ret .= '</table>';
		}
		return($ret);
	}

	/**
	 */
	function curl($params)
	{
		$base = 'http://id.wikipedia.org/w/api.php?format=json';
		foreach ($params as $key => $val)
		{
			$param .= sprintf('&%1$s=%2$s', $key, $val);
		}
		$url = $base . $param;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		$output = curl_exec($ch);
		$ret = json_decode($output, true);
		curl_close($ch);
		return($ret);
	}

	/**
	 */
	function trusted_users()
	{
		$params = array(
			'action'  => 'query',
			'list'    => 'allusers',
			'aulimit' => $this->max_limit,
			'auprop'  => 'blockinfo|editcount|registration',
		);
		$params['augroup'] = 'editor';
		$raw1 = $this->curl($params);
		$this->get_users($raw1, $raw);
		$params['augroup'] = 'sysop';
		$raw2 = $this->curl($params);
		$this->get_users($raw2, $raw);
		$raw = array_unique($raw);
		return($raw);
	}

	/**
	 */
	function get_users($raw, &$users)
	{
		if ($tmp = $raw['query']['allusers'])
			foreach ($tmp as $user)
				$users[] = $user['name'];
	}

	/**
	 */
	function format_summary($changes)
	{
		$max = 50;
		$ret = count($changes) == 1 ? strip_tags($changes[0]['parsedcomment']) : '';
		if (strlen($ret) > $max) $ret = substr($ret, 0, $max) . ' ...';
		return($ret);
	}

	/**
	 */
	function format_diff($diff)
	{
		$num = (($diff > 0) ? ('+' . $diff) : $diff);
		$size = ($diff == 0 ? 'size-null' : ($diff > 0 ? 'size-pos' : 'size-neg'));
		$large = (abs(intval($diff)) >= 500 ? ' size-large' : '');
		$ret = sprintf('<span class="%2$s%3$s">(%1$s)</span>', $num, $size, $large);
		return($ret);
	}

	/**
	 */
	function check_revert($summary)
	{
		$found = false;
		if (strpos($summary, 'dikembalikan ke versi terakhir') !== false) $found = true;
		if (strpos($summary, 'membatalkan revisi') !== false) $found = true;
		return($found);
	}
}