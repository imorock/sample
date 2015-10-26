<?php

/**
 * 評価を数値に変換
 * Enter description here ...
 * @param unknown_type $sogoseitoritu
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function getHyoukaNum( $sogoseitoritu, $dummy3, $dummy2, $dummy1 ){

	if( $sogoseitoritu == 100 ){

		$hyouka = 1;

	}elseif ( $sogoseitoritu >= 80 ){

		$hyouka = 2;

	}elseif ( $sogoseitoritu >= 60 ){

		$hyouka = 3;

	}elseif ( $sogoseitoritu >= 40 ){

		$hyouka = 4;

	}else{

		$hyouka = 5;

	}

	return $hyouka;
}
/*
 * 指導者も見る学生の履歴
 */
function getTrainingRirekiClassNoGakusei ( $gakusei_number, $shutudaikubun_number, $zenken, $startnum, $kensu, $orderkey, $sortkey1, $sortkey2, $dummy3, $dummy2, $dummy1 ){

	mondaitablesetting( $shutudaikubun_number, 0 );

	global $_TEMP_TABLENAME;
	global $_TEMP_FIELDNAME;

	$mysqli = my_mysqli_connect();

	if( 1 ){

		# 最大１年前まで
		$min_date = date("Y-m-d H:i:s",strtotime("-1 year"));

		if( 1 || $shutudaikubun_number == C_VALUE_SHUTUDAIKUBUN_KAISU ){

			switch ( $shutudaikubun_number ) {

				case C_VALUE_SHUTUDAIKUBUN_BUNYA:

					$app_from = "";
					$app_select = "";

					break;

				case C_VALUE_SHUTUDAIKUBUN_KAISU:

					$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;
				break;

				case C_VALUE_SHUTUDAIKUBUN_MARKMONDAI:

					$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;
				break;

				case C_VALUE_SHUTUDAIKUBUN_MARUBATU:

					$mondaikubun_number = C_VALUE_MONDAIKUBUN_MARUBATSU;
				break;

				default:
					;
				break;
			}

			$sql = "SELECT t_traininglog_number, m_bunya_number, m_komoku_number, m_jissikai_number,
			t_traininglog_shutudaisu, t_traininglog_startdate, t_traininglog_enddate, t_traininglog_seitosu, t_traininglog_seitoritu
			{$app_select}
			FROM {$_TEMP_TABLENAME["traininglog"]}
			{$app_from}
			WHERE m_gakusei_number={$gakusei_number} AND m_shutudaikubun_number={$shutudaikubun_number} AND
			t_traininglog_enddate>'{$min_date}'
			ORDER BY t_traininglog_enddate DESC";

			$the_contents_of_execution = "[{$shutudaikubun_number}]トレーニング履歴を取得する（インポートデータ作成用）";
			$result = $mysqli->query($sql);
			if( !$result ){

				sql_error1( "ERROR ". $the_contents_of_execution, "debug_msg1 fred", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__  );
				$update_error = 1;
				$func_res["error"] = 1;
				$func_res["comment"] .= "システムエラー：". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ ."<br />";

			}else{

				sqlReport1_2( $the_contents_of_execution, "debug_msg1", $sql. " => ". $result->num_rows );

				if( $result->num_rows == 0 ){

				}else{

					$insert_data = "INSERT INTO ". C_TABLE_temp_kyoingakusyurireki1. " ( m_gakusei_number,training_counter, m_jissikai_number, m_bunya_number, m_komoku_number, t_traininglog_startdate, t_traininglog_enddate, t_traininglog_seitoritu ) VALUES ";
					$i = 0;
					while( $kunlenlog_row = $result->fetch_array(MYSQLI_ASSOC) ){

						$rireki_count = $i+1;
						$insert_data .= " ( {$gakusei_number}, {$rireki_count},'{$kunlenlog_row["m_jissikai_number"]}','{$kunlenlog_row["m_bunya_number"]}','{$kunlenlog_row["m_komoku_number"]}','{$kunlenlog_row["t_traininglog_startdate"]}','{$kunlenlog_row["t_traininglog_enddate"]}',{$kunlenlog_row["t_traininglog_seitoritu"]} ),";

						$i++;
					}
					$insert_data = rtrim( $insert_data, ',' );

					#*** 結果セットを開放します ***#
				    $result->close();

				    # 履歴の一時テーブルを作成
					$sql = "CREATE TEMPORARY TABLE ". C_TABLE_temp_kyoingakusyurireki1. " (
					`m_gakusei_number` int(8),
					`training_counter` int(8),
					`m_jissikai_number` int(8),
					`m_bunya_number` int(8),
					`m_komoku_number` int(8),
					`t_traininglog_seitoritu` decimal(4,1) NULL,
					`t_traininglog_startdate` datetime NULL,
					`t_traininglog_enddate` datetime NULL,
					PRIMARY KEY (`training_counter`),
					KEY `trylog_IX1` ( t_traininglog_seitoritu ) )";
					$the_contents_of_execution = "履歴の一時テーブルを作成";
					$result_temp = $mysqli->query($sql);
					if( !$result_temp ){

						sql_error1( "ERROR ". $the_contents_of_execution, "debug_msg1 fred", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__  );
						$func_res["error"] = 1;
						$func_res["comment"] .= "システムエラー：". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ ."<br />";

					}elseif( $result_temp ){

						sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result_temp->num_rows );

					    # 履歴の一時テーブルにインポート
					    $sql = $insert_data;
						$result = $mysqli->query($sql);
						$the_contents_of_execution = "履歴の一時テーブルにインポート";
						if( !$result ){

							sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );
							$update_error = 1;
							$func_res["error"] = 1;
							$func_res["comment"] .= "システムエラー：". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__. "<br />";

						}elseif( $result ){

							sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " 結果件数->". $mysqli->affected_rows );

							if( $kensu > 0 ){
								$sql_limit = " LIMIT {$startnum}, {$kensu}";
							}

							if( $sortkey1 ){

								$ascdesc = ( $orderkey == 1 )? "ASC": "DESC";
								#$app_order = "ORDER BY {$sortkey1} IS NULL ASC,  {$sortkey1} {$ascdesc}";
								$app_order = "ORDER BY {$sortkey1} {$ascdesc}";

							}else{

								$app_order = "ORDER BY t_traininglog_enddate DESC";
							}


							switch ( $shutudaikubun_number ) {

								case C_VALUE_SHUTUDAIKUBUN_BUNYA:

									$app_select = ", m_bunya_name, m_komoku_name";
									$app_from = "INNER JOIN ". C_TABLE_m_komoku. " USING( m_komoku_number ) INNER JOIN ". C_TABLE_m_bunya. " ON ". C_TABLE_m_komoku. ".m_bunya_number=". C_TABLE_m_bunya. ".m_bunya_number";

									break;

								case C_VALUE_SHUTUDAIKUBUN_KAISU:

									$app_select = ", m_jissikai_number";

								break;

								case C_VALUE_SHUTUDAIKUBUN_X2TYPE:

									$app_select = ", m_bunya_name";
									$app_from = "INNER JOIN ". C_TABLE_m_bunya. " USING( m_bunya_number )";

								break;

								case C_VALUE_SHUTUDAIKUBUN_MARUBATU:


									$app_select = ", m_bunya_name, m_komoku_name";
									$app_from = "INNER JOIN ". C_TABLE_m_komoku. " USING( m_komoku_number ) INNER JOIN ". C_TABLE_m_bunya. " ON ". C_TABLE_m_komoku. ".m_bunya_number=". C_TABLE_m_bunya. ".m_bunya_number";
									break;

								default:
									;
								break;
							}

							$sql = "SELECT training_counter, t_traininglog_startdate, t_traininglog_enddate, t_traininglog_seitoritu
							{$app_select}
							FROM ". C_TABLE_temp_kyoingakusyurireki1. "
							{$app_from}
							WHERE m_gakusei_number={$gakusei_number}
							{$app_order}
							{$sql_limit}
							";
							$the_contents_of_execution = "一時テーブルからトレーニング履歴を取得する";
							$result = $mysqli->query($sql);
							if( !$result ){

								sql_error1( "ERROR ". $the_contents_of_execution, "debug_msg1 fred", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__  );
								$update_error = 1;
								$func_res["error"] = 1;
								$func_res["comment"] .= "システムエラー：". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ ."<br />";

							}else{

								sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
								$i = 0;
								while( $trainingrireki_row = $result->fetch_array(MYSQLI_ASSOC) ){

									$trainingrireki_ary["training_counter"][$i] = $trainingrireki_row["training_counter"];
									$trainingrireki_ary["t_traininglog_startdate"][$i] = $trainingrireki_row["t_traininglog_startdate"];
									$trainingrireki_ary["t_traininglog_enddate"][$i] = $trainingrireki_row["t_traininglog_enddate"];
									$trainingrireki_ary["t_traininglog_seitoritu"][$i] = $trainingrireki_row["t_traininglog_seitoritu"];
									if( $shutudaikubun_number == C_VALUE_SHUTUDAIKUBUN_KAISU ) {

										$trainingrireki_ary["m_jissikai_number"][$i] = $trainingrireki_row["m_jissikai_number"];

									}else{

										$trainingrireki_ary["m_komoku_name"][$i] = $trainingrireki_row["m_komoku_name"];
										$trainingrireki_ary["m_bunya_name"][$i] = $trainingrireki_row["m_bunya_name"];
									}
									$i++;

								}
							}
						}
					}
				}
			}

		}else{
		}

		if( $zenken ) return $rireki_count;


	}

	/* 接続を閉じます */
	$mysqli->close();

	if( $trainingrireki_ary ) return $trainingrireki_ary;

}

/**
 * 過去問題の出所を取得
 * Enter description here ...
 * @param unknown_type $mondaikubun_number
 * @param unknown_type $shutudaikubun_number
 * @param unknown_type $mondai_number
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function makeKakomonBiko( $mondaikubun_number, $shutudaikubun_number, $mondai_number, $dummy3, $dummy2, $dummy1 ) {

	mondaitablesetting( $shutudaikubun_number, $mondaikubun_number );

	global $_TEMP_TABLENAME;
	global $_TEMP_FIELDNAME;

	$mysqli = my_mysqli_connect();

	$mysqli = my_mysqli_connect();

	$sql = "SELECT m_jissikai_number, m_jissijikanntai_number, m_jissijikanntai_name, m_mondai_junjo, m_bunya_number, m_bunya_name, m_komoku_name
	FROM {$_TEMP_TABLENAME["mondai"]}
	LEFT JOIN ". C_TABLE_m_jissikai. " USING( m_jissikai_number )
	LEFT JOIN ". C_TABLE_m_jissijikanntai. " USING( m_jissijikanntai_number )
	LEFT JOIN ". C_TABLE_m_komoku. " USING( m_komoku_number )
	LEFT JOIN ". C_TABLE_m_bunya. " USING( m_bunya_number )
	WHERE m_mondai_number={$mondai_number}";
	$the_contents_of_execution = "過去問題の出所を取得";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$mondaicount_ary = $result->fetch_array(MYSQLI_ASSOC);
	}

	/* 接続を閉じます */
	$mysqli->close();

	if( $mondaicount_ary ) return $mondaicount_ary;

}

/**
 * 問題中の画像を虫眼鏡アイコン付リンクに書き換える
 * Enter description here ...
 * @param unknown_type $originaltext
 * @param unknown_type $imgdir
 * @param unknown_type $type
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function chikanZoomImageTag( $originaltext, $imgdir, $type, $filename, $dummy2, $dummy1 ){

	# 前側[IMG]で問題文を区切る
	$mondai_kugiri_ary["frontside"] = explode("[IMG]", $originaltext);

	for ($i = 0; $i < count($mondai_kugiri_ary["frontside"]); $i++) {

		# [/IMG]があれば処理
		if( strstr( $mondai_kugiri_ary["frontside"][$i], '[/IMG]') ){

			# 後側[/IMG]で問題文を区切る
			$mondai_kugiri_ary["rearside"] = explode("[/IMG]", $mondai_kugiri_ary["frontside"][$i]);

			if( 1 ){

				# 問題文に追加
				$mondai_bun1 .= chikanImageTag ( $mondai_kugiri_ary["rearside"][0], C_HTTPDIR_TRAINING_IMG, 2, $filename, 0, 0 );

			}else{

				# FEのタイプ [IMG]画像ファイル名[///]画像ファイル名[/IMG]

				# ファイル名を取得
				$image_filename_ary = explode("[///]", $mondai_kugiri_ary["frontside"][$i]);

				# 問題文に追加
				$mondai_bun1 .= chikanImageTag ( $image_filename_ary[0], C_HTTPDIR_TRAINING_IMG, 2, $filename, 0, 0 );
				if( $mondai_kugiri_ary["rearside"][1] ) $mondai_bun1 .= $mondai_kugiri_ary["rearside"][1];

			}


		}else{

			# 問題文に追加
			$mondai_bun1 .= $mondai_kugiri_ary["frontside"][$i];
		}
	}

	return $mondai_bun1;
}

/*
 * 分野を取得する
 */
function getBunya_One( $bunya_number, $dummy3, $dummy2, $dummy1 ){

	if( $bunya_number ){

		$mysqli = my_mysqli_connect();

		$sql = "SELECT * FROM ". C_TABLE_m_bunya. " WHERE m_bunya_number={$bunya_number}";
		$the_contents_of_execution = "分野を取得する（一件）";
		$result = $mysqli->query($sql);
		if( !$result ){

			sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

		}elseif( $result ){

			sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
			$bunya_ary = $result->fetch_array(MYSQLI_ASSOC);

			#*** 結果セットを開放します ***#
		    $result->close();
		}
		/* 接続を閉じます */
		$mysqli->close();

		if( $bunya_ary ) return $bunya_ary;
	}
}
/*
 * 分野を取得する
 */
function getBunya_List( $jissikai_number, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$app_where = 1;
	if( $jissikai_number>0 ){

		$app_from = " INNER JOIN ". C_TABLE_m_komoku. " USING( m_bunya_number ) INNER JOIN ". C_TABLE_m_kakomon. " USING( m_komoku_number ) ";
		$app_where = "m_jissikai_number={$jissikai_number} GROUP BY m_bunya_number";
	}

	$sql = "SELECT * FROM ". C_TABLE_m_bunya. "{$app_from} WHERE {$app_where} ORDER BY m_bunya_number ASC";
	$the_contents_of_execution = "分野を取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $bunyalist_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$bunyalist_ary["m_bunya_number"][$i] = $bunyalist_row["m_bunya_number"];
			$bunyalist_ary["m_bunya_name"][$i] = $bunyalist_row["m_bunya_name"];
			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $bunyalist_ary ) return $bunyalist_ary;

}

/*
 * テーブル名、フィールド名を変更する
 * ※出題区分別
 */
function mondaitablesetting( $shutudaikubun_number, $mondaikubun_number ){

	global $_TEMP_TABLENAME;
	global $_TEMP_FIELDNAME;

	#global $mondaikubun_number;

	if( $shutudaikubun_number ){

		switch ( $shutudaikubun_number ) {

			case C_VALUE_SHUTUDAIKUBUN_BUNYA:

				$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;
			break;

			case C_VALUE_SHUTUDAIKUBUN_KAISU:

				$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;
			break;

			case C_VALUE_SHUTUDAIKUBUN_MARKMONDAI:

				#$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;

			break;

			case C_VALUE_SHUTUDAIKUBUN_MARUBATU:

				$mondaikubun_number = C_VALUE_MONDAIKUBUN_MARUBATSU;

			break;

			case C_VALUE_SHUTUDAIKUBUN_X2TYPE:

				$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;

			break;

			case C_VALUE_SHUTUDAIKUBUN_JITTI:

				$mondaikubun_number = C_VALUE_MONDAIKUBUN_KAKOMON;

			break;

			default:
				;
			break;
		}
	}

	# ○×問題
	if( $mondaikubun_number == C_VALUE_MONDAIKUBUN_MARUBATSU ){

		$_TEMP_TABLENAME["mondai"] = C_TABLE_m_mondai;
		$_TEMP_TABLENAME["sentakusi"] = C_TABLE_m_sentakusi;
		$_TEMP_TABLENAME["toibetukaito"] = C_TABLE_t_toibetukaito;
		$_TEMP_TABLENAME["toibetukaitosi"] = C_TABLE_t_toibetukaitosi;
		$_TEMP_TABLENAME["traininglog"] = C_TABLE_t_traininglog;
		$_TEMP_TABLENAME["retrylog"] = C_TABLE_t_retrylog;
		$_TEMP_TABLENAME["weakmondailist"] = C_TABLE_t_weakmondailist;

	# 過去問題
	}else{

		$_TEMP_TABLENAME["mondai"] = C_TABLE_m_kakomon;
		$_TEMP_TABLENAME["sentakusi"] = C_TABLE_m_sentakusi_kakomon;
		$_TEMP_TABLENAME["toibetukaito"] = C_TABLE_t_toibetukaito_kakomon;
		$_TEMP_TABLENAME["toibetukaitosi"] = C_TABLE_t_toibetukaitosi_kakomon;
		$_TEMP_TABLENAME["traininglog"] = C_TABLE_t_traininglog_kakomon;
		$_TEMP_TABLENAME["retrylog"] = C_TABLE_t_retrylog_kakomon;
		$_TEMP_TABLENAME["weakmondailist"] = C_TABLE_t_weakmondailist_kakomon;
		$_TEMP_TABLENAME["result"] = C_TABLE_t_result;

	}

	#-----------

	return $mondaikubun_number;
}

/**
 * 問題を取得する
 * Enter description here ...
 * @param unknown_type $mondai_number
 * @param unknown_type $nendo_number
 * @param unknown_type $ki_number
 * @param unknown_type $junjo
 * @param unknown_type $img_chikan_flag
 * @param unknown_type $dummy4
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function getMondai_One( $mondaikubun_number, $mondai_number, $jissikai_number, $jissijikantai_number, $junjo, $img_chikan_flag, $filename, $dummy3, $dummy2, $dummy1) {

	if( 1 ){

		mondaitablesetting( 0, $mondaikubun_number );

		global $_TEMP_TABLENAME;
		global $_TEMP_FIELDNAME;

		$mysqli = my_mysqli_connect();

		if( $jissikai_number && $jissijikantai_number && $junjo>0 ){

			$app_where = "m_jissikai_number={$jissikai_number} AND m_jissijikantai_number={$jissijikantai_number} AND m_mondai_junjo={$junjo}";

		}else{

			$app_where = "m_mondai_number={$mondai_number}";
		}

		$sql = "SELECT *
		FROM {$_TEMP_TABLENAME["mondai"]}
		LEFT JOIN ". C_TABLE_m_komoku. " USING( m_komoku_number )
		LEFT JOIN ". C_TABLE_m_bunya. " USING( m_bunya_number )
		LEFT JOIN ". C_TABLE_m_shutudaitype. " USING( m_shutudaitype_number )
		WHERE {$app_where}";
		$the_contents_of_execution = "問題を取得する（1件）";
		$result = $mysqli->query($sql);
		if( !$result ){

			sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

		}elseif( $result ){

			sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
			$mondai_ary = $result->fetch_array(MYSQLI_ASSOC);

			$mondai_bun1 = chikanZoomImageTag( $mondai_ary["m_mondai_bun1"], C_HTTPDIR_TRAINING_IMG, $img_chikan_flag, $filename, 0, 0);
			$mondai_bun2 = chikanZoomImageTag( $mondai_ary["m_mondai_bun2"], C_HTTPDIR_TRAINING_IMG, $img_chikan_flag, $filename, 0, 0);
			$mondai_ary["m_mondai_bun1"] = ( $img_chikan_flag )? $mondai_bun1: $mondai_ary["m_mondai_bun1"];
			$mondai_ary["m_mondai_bun2"] = ( $img_chikan_flag )? $mondai_bun2: $mondai_ary["m_mondai_bun2"];

			$mondai_mondaikaisetu = chikanZoomImageTag( $mondai_ary["m_mondai_mondaikaisetu"], C_HTTPDIR_TRAINING_IMG, $img_chikan_flag, $filename, 0, 0);
			$mondai_ary["m_mondai_mondaikaisetu"] = ( $img_chikan_flag )? $mondai_mondaikaisetu: $mondai_ary["m_mondai_mondaikaisetu"];

			#*** 結果セットを開放します ***#
		    $result->close();

			$sql = "SELECT m_sentakusi_number, m_sentakusi_junjo, m_sentakusi_text, m_sentakusi_kaisetu, m_sentakusi_bingo
			FROM {$_TEMP_TABLENAME["sentakusi"]}
			WHERE m_mondai_number={$mondai_ary["m_mondai_number"]} ORDER BY m_sentakusi_junjo ASC";
			$the_contents_of_execution = "選択肢を取得する（リスト）";
			$result = $mysqli->query($sql);
			if( !$result ){

				sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

			}elseif( $result ){

				sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
				while( $sentakusi_row = $result->fetch_array(MYSQLI_ASSOC) ){

					$mondai_ary["m_sentakusi_number"][$sentakusi_row["m_sentakusi_junjo"]] = $sentakusi_row["m_sentakusi_number"];
					$mondai_ary["m_sentakusi_junjo"][$sentakusi_row["m_sentakusi_junjo"]] = $sentakusi_row["m_sentakusi_junjo"];
					$mondai_ary["m_sentakusi_text"][$sentakusi_row["m_sentakusi_junjo"]] = ( $img_chikan_flag )? chikanImageTag ( $sentakusi_row["m_sentakusi_text"], C_HTTPDIR_TRAINING_IMG, $img_chikan_flag, 0, 0, 0 ): $sentakusi_row["m_sentakusi_text"];
					$mondai_ary["m_sentakusi_kaisetu"][$sentakusi_row["m_sentakusi_junjo"]] = ( $img_chikan_flag )? chikanImageTag ( $sentakusi_row["m_sentakusi_kaisetu"], C_HTTPDIR_TRAINING_IMG, $img_chikan_flag, 0, 0, 0 ): $sentakusi_row["m_sentakusi_kaisetu"];
					$mondai_ary["m_sentakusi_bingo"][$sentakusi_row["m_sentakusi_junjo"]] = $sentakusi_row["m_sentakusi_bingo"];
				}

				#*** 結果セットを開放します ***#
			    $result->close();
			}
		}

		/* 接続を閉じます */
		$mysqli->close();

		if( $mondai_ary ) return $mondai_ary;

	}
}

/*
 * 問題区分を取得する
 */
function getMondaikubun_List( $dummy3, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$sql = "SELECT * FROM ". C_TABLE_m_mondaikubun. " WHERE 1 ORDER BY m_mondaikubun_number ASC";
	$the_contents_of_execution = "問題区分を取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $mondaikubunlist_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$mondaikubunlist_ary["m_mondaikubun_number"][$i] = $mondaikubunlist_row["m_mondaikubun_number"];
			$mondaikubunlist_ary["m_mondaikubun_name"][$i] = $mondaikubunlist_row["m_mondaikubun_name"];
			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $mondaikubunlist_ary ) return $mondaikubunlist_ary;

}

/*
 * 出題タイプを取得する
 */
function getShutudaiType_List( $dummy3, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$sql = "SELECT * FROM ". C_TABLE_m_shutudaitype. " WHERE 1 ORDER BY m_shutudaitype_junjo DESC";
	$the_contents_of_execution = "出題タイプを取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $shutudaitypelist_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$shutudaitypelist_ary["m_shutudaitype_number"][$i] = $shutudaitypelist_row["m_shutudaitype_number"];
			$shutudaitypelist_ary["m_shutudaitype_name"][$i] = $shutudaitypelist_row["m_shutudaitype_name"];
			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $shutudaitypelist_ary ) return $shutudaitypelist_ary;

}

/*
 * 実施回名を取得する
 */
function getJissijikanntai_One( $jissijikanntai_number, $dummy3, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$sql = "SELECT m_jissijikanntai_name FROM ". C_TABLE_m_jissijikanntai. " WHERE m_jissijikanntai_number={$jissijikanntai_number}";
	$the_contents_of_execution = "実施時間帯を取得する";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$jissikainame_ary = $result->fetch_array(MYSQLI_ASSOC);

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $jissikainame_ary ) return $jissikainame_ary;
}

/*
 * 実施回を取得する
 */
function getJissikai_List( $dummy3, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$sql = "SELECT * FROM ". C_TABLE_m_jissikai. " WHERE 1 ORDER BY m_jissikai_number DESC";
	$the_contents_of_execution = "実施回を取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $jissikailist_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$jissikailist_ary["m_jissikai_number"][$i] = $jissikailist_row["m_jissikai_number"];
			$jissikailist_ary["m_jissikai_status"][$i] = $jissikailist_row["m_jissikai_status"];

			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $jissikailist_ary ) return $jissikailist_ary;

}

/*
 * 実施時間帯を取得する
 */
function getJissijikanntai_List( $dummy3, $dummy2, $dummy1 ){

	$mysqli = my_mysqli_connect();

	$sql = "SELECT * FROM ". C_TABLE_m_jissijikanntai. " WHERE 1 ORDER BY m_jissijikanntai_number ASC";
	$the_contents_of_execution = "実施時間帯を取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $jissijikanntai_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$jissijikanntai_ary["m_jissijikanntai_number"][$i] = $jissijikanntai_row["m_jissijikanntai_number"];
			$jissijikanntai_ary["m_jissijikanntai_name"][$i] = $jissijikanntai_row["m_jissijikanntai_name"];
			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $jissijikanntai_ary ) return $jissijikanntai_ary;

}

/*
 * 項目を取得する
 */
function getKomoku_List( $shutudaikubun_number, $bunya_number, $mondai_count, $dummy1 ){

	mondaitablesetting( $shutudaikubun_number, 0 );

	global $_TEMP_TABLENAME;
	global $_TEMP_FIELDNAME;

	$mysqli = my_mysqli_connect();

	$mysqli = my_mysqli_connect();

	$app_where = ( $bunya_number )? "m_bunya_number={$bunya_number}": "1";

	if( $mondai_count && $shutudaikubun_number ){

		$app_select = ", COUNT( m_mondai_number ) as mondai_count";
		$app_from = " LEFT JOIN {$_TEMP_TABLENAME["mondai"]} USING( m_komoku_number ) ";
		$app_where .= " GROUP BY m_komoku_number";
	}

	$sql = "SELECT *{$app_select} FROM ". C_TABLE_m_komoku. "
	{$app_from}
	WHERE {$app_where} ORDER BY m_bunya_number, m_komoku_number ASC";
	$the_contents_of_execution = "項目を取得する（リスト）";
	$result = $mysqli->query($sql);
	if( !$result ){

		sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

	}elseif( $result ){

		sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
		$i = 0;
		while( $komokulist_row = $result->fetch_array(MYSQLI_ASSOC) ){

			$komokulist_ary["m_komoku_number"][$i] = $komokulist_row["m_komoku_number"];
			$komokulist_ary["m_komoku_name"][$i] = $komokulist_row["m_komoku_name"];

			$komokulist_ary["mondai_count"][$i] = $komokulist_row["mondai_count"];
			$i++;
		}

		#*** 結果セットを開放します ***#
	    $result->close();
	}
	/* 接続を閉じます */
	$mysqli->close();

	if( $komokulist_ary ) return $komokulist_ary;

}

/*
 * 項目を取得する
 */
function getKomoku_One( $komoku_number, $dummy3, $dummy2, $dummy1 ){

	if( $komoku_number ){

		$mysqli = my_mysqli_connect();

		$sql = "SELECT ". C_TABLE_m_komoku.".*, m_bunya_number, m_bunya_name FROM ". C_TABLE_m_komoku. " INNER JOIN ". C_TABLE_m_bunya." USING( m_bunya_number )
		WHERE m_komoku_number={$komoku_number}";
		$the_contents_of_execution = "項目を取得する（一件）";
		$result = $mysqli->query($sql);
		if( !$result ){

			sql_error1( $the_contents_of_execution, "debug_msg1", $sql. " => ". $mysqli->error. "<br />". __FUNCTION__. " File:". __FILE__. " Line:". __LINE__ );

		}elseif( $result ){

			sqlReport1_2( $the_contents_of_execution, "debug_msg1 fblue", $sql. " => ". $result->num_rows );
			$komoku_ary = $result->fetch_array(MYSQLI_ASSOC);

			#*** 結果セットを開放します ***#
		    $result->close();
		}
		/* 接続を閉じます */
		$mysqli->close();

		if( $komoku_ary ) return $komoku_ary;
	}
}


/**
 * データベースへ接続
 * Enter description here ...
 */
function my_mysqli_connect(){

	$mysqli = new mysqli( C_DB_HOST, C_DB_USER, C_DB_PASSWORD, C_DB_NAME);

	/* 接続状況をチェックします */
	if (mysqli_connect_errno()) {
	    printf("Connect failed: %s\n", mysqli_connect_error());
	    exit();
	}

	$sql = "SET NAMES utf8";
	$mysqli->query($sql) or exit($sql);

	return $mysqli;
}

/**
 * エスケープが必要な文字のエンコードとでコード
 * Enter description here ...
 * @param unknown_type $originaltext
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function textEncode ( $originaltext, $dummy3, $dummy2, $dummy1 ){

	global $debug_msg;

	$replacetext = str_replace( ",", "[COMMA]", $originaltext );

	return $replacetext;

}
function textDecode ( $originaltext, $dummy3, $dummy2, $dummy1 ){

	global $debug_msg;

	$replacetext = str_replace( "[COMMA]", ",", $originaltext );

	return $replacetext;

}

/**
 * イメージタグの書き換え
 * Enter description here ...
 * @param unknown_type $originaltext
 * @param unknown_type $imgdir
 * @param unknown_type $type
 * @param unknown_type $dummy3
 * @param unknown_type $dummy2
 * @param unknown_type $dummy1
 */
function chikanImageTag ( $originaltext, $imgdir, $type, $filename, $dummy2, $dummy1 ){

	$retStr = makeRandStr( 2, 1, 0, 1 );

	if( !$filename ) $filename = "zoom1.php";

	if( $type == 2 ){

$replacetext .= <<< EOF
<p style="margin-bottom: 0em;;"><a class="zoom1" style="width: auto;" onclick="javascript:submitModeSousin_One_blank( 'f1', 'mode', '{$filename}', '
EOF;
$replacetext .= $originaltext;
$replacetext .= <<< EOF
', 0, 0 );"><img src="$imgdir
EOF;
$replacetext .= $originaltext;
$replacetext .= <<< EOF
?$retStr" style="width: 210px; height: auto;" /></a></p><a class="zoom1_icon" onclick="submitModeSousin_One_blank( 'f1', 'mode', '{$filename}', '
EOF;
$replacetext .= $originaltext;
$replacetext .= <<< EOF
');">&nbsp;</a>
EOF;

	}elseif( 0 ){

$imagetag_front1 = <<< EOF
		<p style="margin-bottom: 0em;;"><a class="zoom1" style="width: auto;" href="javascript:submitModeSousin_One_blank( 'f1', 'mode', 'zoom1.php', '
EOF;
$imagetag_front2 = <<< EOF
', 0, 0 );"><img src="$imgdir
EOF;
$imagetag_end = <<< EOF
?$retStr" style="width: 210px; height: auto;" /></a></p><p style="clear:both; margin-bottom: 1em; padding-left: 3px;"><img src="./images/zoom1.png" style="width: auto; height: auto;" /></p>
EOF;

		$replacetext = str_replace( "[IMG]", $imagetag_front1, $originaltext);
		$replacetext = str_replace( "[///]", $imagetag_front2, $replacetext);
		$replacetext = str_replace( "[/IMG]", $imagetag_end, $replacetext);

	}else{

		$imagetag_front = '<img src="'. $imgdir;
		$imagetag_end = '?'. $retStr. '" />';

		$replacetext = str_replace( "[IMG]", $imagetag_front, $originaltext);
		$replacetext = str_replace( "[/IMG]", $imagetag_end, $replacetext);

	}

	return $replacetext;

}


# =================================================================================
# デバック表示
#
# =================================================================================

function sqlReport1 ( $run_query, $styleclass, $sql ){

	global $debug_msg;
	global $table;

	$debug_msg .= "<p><span class='". $styleclass. "'>". $run_query.  "</span>: ". $sql. "<br>( MEMORY: ". memory_get_usage(). " b )</p>";
	$debug_msg .= "<p><span class='fblue'>結果件数</span>: ". mysql_affected_rows(). "件</p>";

}

function sqlReport2 ( $run_query, $styleclass, $sql ){

	global $debug_msg;
	global $table;

	$debug_msg .= "<p><span class='". $styleclass. "'>". $run_query.  "</span>: ". $sql. "</p>";

}

function sqlReport1_2 ( $run_query, $styleclass, $sql ){

	global $debug_msg;

	$debug_msg .= "<p><span class='". $styleclass. "'>". $run_query.  "</span>: ". $sql. "<br>( MEMORY: ". memory_get_usage(). " b )</p>";

}
# 2015.07.08
function sqlReport1_backtrace ( $run_query, $styleclass, $sql, $backtrace_ary, $dummy3, $dummy2, $dummy1 ){

	global $debug_msg;

	$debug_msg .= "<p><span class='". $styleclass. "'>". $run_query.  "</span>: ". $sql. " ". "<br>[file]=>{$backtrace_ary[0]["file"]}<br>[line]=>{$backtrace_ary[0]["line"]}<br>[function]=>{$backtrace_ary[0]["function"]}<br>( MEMORY: ". memory_get_usage(). " b )</p>";

}

# =================================================================================
# SQLでエラー
#
# =================================================================================
function sql_error1( $title, $sql, $error ) {
	sqlReport1_2( "ERROR $title", "debug_msg1 fred", $sql. " ". $error );
}
function sql_error2( $title, $sql, $error, $backtrace_ary ) {

	global $debug_msg;

	$debug_msg .= "<p><span class='debug_msg1 fred'>{ERROR $title}</span>: ". $sql. " ". $error. "<br>[file]=>{$backtrace_ary[0]["file"]}<br>[line]=>{$backtrace_ary[0]["line"]}<br>[function]=>{$backtrace_ary[0]["function"]}<br>( MEMORY: ". memory_get_usage(). " b )</p>";
}

# =================================================================================
# フォーム入力内容をエスケープ
#
# =================================================================================
function str_check_mysqli( $string, $mysqli ){

	//タグを無効にする
	#$string=htmlspecialchars( $string );	//特にエラーは出ませんでしたが、今回は記述しなくてもいいっぽいです
	//
	//
	if( get_magic_quotes_gpc() ){
		$string=stripslashes( $string );
	}
	//SQLコマンド用の文字列にエスケープする
	$string = $mysqli->real_escape_string($string);

	return $string;
}

# =================================================================================
# ランダム文字列
#
# =================================================================================
function makeRandStr( $len, $komoji, $omoji, $suji ) {

	$strKomoji = "abcdefghijklmnopqrstuvwxyz";
	$strOmoji = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$strSuji = "0123456789";

	$strElem = "";
	if( $komoji ) $strElem .= $strKomoji;
	if( $omoji ) $strElem .= $strOmoji;
	if( $suji ) $strElem .= $strSuji;

	$strElemArray = preg_split("//", $strElem, 0, PREG_SPLIT_NO_EMPTY);

	$retStr = "";

	srand( (double)microtime() * 100000);

	for( $i=0; $i<$len; $i++ ) {

	    $retStr .= $strElemArray[array_rand($strElemArray, 1)];

	}

	return $retStr;
}

# =================================================================================
# 文字列を指定文字数に切り詰める
#
# =================================================================================
function changeStrLen( $str, $limit, $dummy2, $dummy1 ) {

	//全角・半角問わずバイト数でなく文字数を取得する。
	$iCount = mb_strlen( $str, "utf-8" );

	//全角・半角問わず***文字より多い場合、***文字に切り詰める
	if( $iCount > $limit ){
	    $change_str = mb_substr( $str, 0, $limit, "utf-8" );
	    $change_str .= "...";
	}else{
		$change_str = $str;
	}

	return $change_str;
}

# =================================================================================
# トレーニングサイトでのエラーを保存【lv2】2011.11.01
#
#
#
# =================================================================================
function insErrorLog ( $user_number, $session_number, $error_kiroku1, $error_kiroku2, $error_kiroku3, $mysqli, $dummy1 ){

	if( $mysqli ){

		$error_kiroku1 = str_check_mysqli( $error_kiroku1, $mysqli );
		$error_kiroku2 = str_check_mysqli( $error_kiroku2, $mysqli );
		$error_kiroku3 = str_check_mysqli( $error_kiroku3, $mysqli );

		# トレーニングサイトでのエラーを保存
		$sql = "INSERT INTO ". C_TABLE_t_training_errorlog. "
		( m_gakusei_number, t_session_number, t_training_errorlog_session_id, t_training_errorlog_url, t_training_errorlog_kiroku1, t_training_errorlog_kiroku2, t_training_errorlog_kiroku3, t_training_errorlog_biko1, t_training_errorlog_ipaddress, t_training_errorlog_browser, t_training_errorlog_date )
		VALUES ( '{$user_number}', '{$session_number}', '". session_id(). "', '{$_SERVER["REQUEST_URI"]}', '{$error_kiroku1}', '{$error_kiroku2}', '{$error_kiroku3}', '', '{$_SERVER["REMOTE_ADDR"]}',  '{$_SERVER["HTTP_USER_AGENT"]}', '". date("Y-m-d H:i:s"). "' )";
		$the_contents_of_execution = "トレーニングサイトでのエラーを保存";
		$result = $mysqli->query($sql);
		if( !$result ){

			sql_error1( $the_contents_of_execution, $sql, $mysqli->error );

		}elseif( $result ){

			sqlReport1_2( $the_contents_of_execution, "debug_msg1", $sql. " 結果件数->". $mysqli->affected_rows );

		}

	}else{

		$error_kiroku1 = str_check( $error_kiroku1 );
		$error_kiroku2 = str_check( $error_kiroku2 );
		$error_kiroku3 = str_check( $error_kiroku3 );

		# トレーニングサイトでのエラーを保存
		$sql = "INSERT INTO ". C_TABLE_t_training_errorlog. "
		( m_gakusei_number, t_session_number, t_training_errorlog_session_id, t_training_errorlog_url, t_training_errorlog_kiroku1, t_training_errorlog_kiroku2, t_training_errorlog_kiroku3, t_training_errorlog_biko1, t_training_errorlog_ipaddress, t_training_errorlog_browser, t_training_errorlog_date )
		VALUES ( '{$user_number}', '{$session_number}', '". session_id(). "', '{$_SERVER["REQUEST_URI"]}', '{$error_kiroku1}', '{$error_kiroku2}', '{$error_kiroku3}', '', '{$_SERVER["REMOTE_ADDR"]}',  '{$_SERVER["HTTP_USER_AGENT"]}', '". date("Y-m-d H:i:s"). "' )";
		if(!$res1=mysql_query($sql)){

			sqlReport1( "SQL失敗 → ". mysql_error(), "debug_msg1", $sql );

		}else{

			sqlReport1( "トレーニングサイトでのエラーを保存", "debug_msg1", $sql );
		}
	}

}

#insErrorLog ( $gakusei_number, "", "大項目総合訓練ログに終了日時を書き込み", mysql_error(), $sql, 0, 0 );

?>