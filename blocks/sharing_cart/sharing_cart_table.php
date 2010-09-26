<?php
/**
 *  sharing_cart テーブル操作クラス
 */

class sharing_cart_table
{
	/**
	 *	ユニーク名(=バックアップ作成時刻)からZIPファイル名を生成
	 */
	public static function gen_zipname($time)
	{
		return 'shared-'.date('Ymd-His', $time).'.zip';
	}
	
	/**
	 *	レコード取得 + 以前のバージョンのデータで足りない値があれば補完
	 */
	public static function get_record_by_id($id)
	{
		$record = get_record('sharing_cart', 'id', $id);
		if (!$record)
			return NULL;
		$updated = FALSE;
		if (empty($record->file)) {
			// `file`フィールドが空なら自動生成
			$record->file = self::gen_zipname($record->time);
			$updated = TRUE;
		}
		if ($updated) {
			// データの更新が発生したらDBも更新
			update_record('sharing_cart', $record);
		}
		return $record;
	}
	
	/**
	 *	レコード挿入 + 表示順を再構築
	 */
	public static function insert_record($record)
	{
		if (empty($record->file)) {
			// `file`フィールドが空なら自動生成
			$record->file = self::gen_zipname($record->time);
		}
		if (!insert_record('sharing_cart', $record))
			return FALSE;
		self::renumber($record->user);
		return TRUE;
	}
	
	/**
	 *	レコード更新 + 表示順を再構築
	 */
	public static function update_record($record)
	{
		if (!update_record('sharing_cart', $record))
			return FALSE;
		self::renumber($record->user);
		return TRUE;
	}
	
	/**
	 *	レコード削除 + 表示順を再構築
	 */
	public static function delete_record($record)
	{
		if (!delete_records('sharing_cart', 'id', $record->id))
			return FALSE;
		self::renumber($record->user);
		return TRUE;
	}
	
	/**
	 *	Sharing Cart ブロック内でのアイテム表示順の通し番号を振りなおす
	 */
	public static function renumber($user_id = NULL)
	{
		if (empty($user_id)) {
			$user_id = $GLOBALS['USER']->id;
		}
		if ($records = get_records('sharing_cart', 'user', $user_id)) {
			$tree = array();
			foreach ($records as $record) {
				if (!isset($tree[$record->tree]))
					$tree[$record->tree] = array();
				$tree[$record->tree][] = $record;
			}
			foreach ($tree as $items) {
				usort($items, array(__CLASS__, 'renumber_cmp'));
				foreach ($items as $i => $item) {
					$item->sort = $i;
					$item->text = addslashes($item->text);
					if (!update_record('sharing_cart', $item))
					    return FALSE;
				}
			}
		}
		return TRUE;
	}
	protected static function renumber_cmp($a, $b)
	{
		// 既に振られていればそれに従う
		if ($a->sort < $b->sort) return -1;
		if ($a->sort > $b->sort) return +1;
		// 番号が重複していた場合は文字順に並べ替え
		return strnatcasecmp($a->text, $b->text);
	}
}

?>