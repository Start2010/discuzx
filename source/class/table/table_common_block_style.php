<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_block_style.php 31736 2012-09-26 02:23:48Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_block_style extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_block_style';
		$this->_pk    = 'styleid';

		parent::__construct();
	}

	public function fetch_all_by_blockclass($blockclass) {
		return $blockclass ? DB::fetch_all('SELECT * FROM %t WHERE blockclass=%s', array($this->_table, $blockclass), $this->_pk) : array();
	}

	public function fetch_all_by_hash($hash) {
		return $hash ? DB::fetch_all('SELECT * FROM %t WHERE `hash` IN (%n)', array($this->_table, $hash), $this->_pk) : array();
	}

	/**
	 * 仅供后台管理使用函数
	 * @param string $wheresql
	 * @return int
	 */
	public function count_by_where($wheresql) {
		$wheresql = $wheresql ? ' WHERE '.(string)$wheresql : '';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table).$wheresql);
	}

	/**
	 * 仅供后台管理使用函数
	 * @param string $wheresql
	 * @param string $ordersql
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	public function fetch_all_by_where($wheresql, $ordersql, $start, $limit) {
		$wheresql = $wheresql ? ' WHERE '.(string)$wheresql : '';
		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).$wheresql.' '.(string)$ordersql.DB::limit($start, $limit), null, $this->_pk ? $this->_pk : '');
	}

	public function insert_batch($styles) {
		$inserts = array();
		foreach($styles as $value) {
			if(!empty($value['blockclass'])) {
				$value = daddslashes($value);
				$inserts[] = "('$value[blockclass]', '$value[name]', '$value[template]', '$value[hash]', '$value[getpic]', '$value[getsummary]', '$value[settarget]', '$value[fields]', '$value[moreurl]')";
			}
		}
		if(!empty($inserts)) {
			DB::query('INSERT INTO '.DB::table($this->_table)."(`blockclass`, `name`, `template`, `hash`, `getpic`, `getsummary`, `settarget`, `fields`, `moreurl`) VALUES ".implode(',',$inserts));
		}
	}

	/**
	 * 依据主键更新记录
	 * @param string|int $val 主键值
	 * @param array $data 更新的字段和值的list
	 * @param boolean $unbuffered 迅速返回
	 * @param boolan $low_priority 延迟更新
	 * @return boolean
	 */
	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if(($val = dintval($val, true)) && $data && is_array($data)) {
			$this->_pre_cache_key = 'blockstylecache_';
			$this->_cache_ttl = getglobal('setting/memory/diyblock');
			$this->_allowmem = getglobal('setting/memory/diyblock') && memory('check'); //是否允许缓存
			$this->clear_cache($val); //因缓存中保存的数据是array，而$data中的数据是serialize，无法进行合并所以直接删除缓存
			return parent::update($val, $data, $unbuffered, $low_priority);
		}
		return false;
	}

	/**
	 * 依据主键删除某条记录
	 * @param string|int $val 主键值
	 * @return boolean
	 */
	public function delete($val, $unbuffered = false) {
		if(($val = dintval($val, true))) {
			$this->_pre_cache_key = 'blockstylecache_';
			$this->_cache_ttl = getglobal('setting/memory/diyblock');
			$this->_allowmem = getglobal('setting/memory/diyblock') && memory('check'); //是否允许缓存
			return parent::delete($val, $unbuffered);
		}
		return false;
	}
}

?>