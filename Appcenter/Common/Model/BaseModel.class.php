<?php
namespace Common\Model;

class BaseModel {
	
	/**
	 * 计算分页数据
	 * @param $totalRows
	 * @param int $listRows
	 * @return array
	 */
	public function paginator($totalRows, $listRows = 20) {
		$page = [
			'totalRows' => $totalRows,
			'totalPage' => ceil($totalRows / $listRows),
			'everyPage' => $listRows,
		];
		return $page;
	}
	
}
?>