<?php
/*
|--------------------------------------------------------------------------
| 自定义常用函数库
|--------------------------------------------------------------------------
*/

/** 生成树形结构
 * @param array $list       原始数据
 * @param string $str       树形结构样式 如："<option value='\$id' \$selected>\$space \$name</option>"
 * @param int $parent_id    一级项目ID
 * @param int $level        初始层级
 * @param array $icon       树形图标
 * @param string $nbsp      图标空格
 * @return string           树形结构字符串
 */
function get_tree($list=[], $str="<option value='\$id' \$selected>\$space \$name</option>", $parent_id=0, $level=1, $tree_icon=['&nbsp;┃','&nbsp;┣','&nbsp;┗'], $tree_nbsp='&nbsp;'){
    $result='';
    if(empty($list)){
        return $result;
    }
    $array=get_childs($list,$parent_id);
    $childs=$array['childs'];
    $new_list=$array['new_list'];
    $num=count($childs);
    if(empty($childs)){
        return $result;
    }

    $i=1;
    foreach ($childs as $child){
        $child=$child->toArray();
        $space='';
        for($j=1;$j<$level;$j++){
            if($j==1){
                $space .=$tree_nbsp;
            }else{
                $space .=$tree_icon[0].$tree_nbsp;
            }
        }
        if($level!=1){
            if($i==$num){
                $space.=$tree_icon[2];
            }else{
                $space.=$tree_icon[1];
            }
        }
        @extract($child);
        eval("\$nstr = \"$str\";");

        $result .=$nstr;
        $result .=get_tree($new_list,$str,$child['id'],$level+1,$tree_icon,$tree_nbsp);

        $i++;
    }

    return $result;
}

/** 获取集合中的子元素
 * @param array $list       数据集合
 * @param int $parent_id    上级ID
 * @return array            子元素集合
 */
function get_childs($list, $parent_id){
    $array=[];
    foreach ($list as $key=>$value){
        if($value->parent_id==$parent_id){
            $array[]=$value;
            unset($list[$key]);
        }
    }
    return ['childs'=>$array,'new_list'=>$list];
}

/** 批量 更新或插入数据的sql
 * @param string $table         数据表名
 * @param array $insert_columns 数据字段
 * @param array $values         原始数据
 * @param array|string $update_columns 更新字段
 * @return bool|array          返回false(条件不符)，返回array(sql语句)
 */
function batch_update_or_insert_sql($table='', $insert_columns=[], $values=[], $update_columns=[]){
    if(empty($table) || empty($insert_columns) || empty($values) || empty($update_columns)){
        return false;
    }
    // 数据字段必须包含更新字段
    if(is_string($update_columns)){
        if(!in_array($update_columns,$insert_columns)){
            return false;
        }
    }else{
        $common_columns= array_intersect($insert_columns,$update_columns);
        sort($common_columns);
        sort($update_columns);
        if($common_columns != $update_columns){
            return false;
        }
    }

    //数据字段
    $sql_insert_columns=[];
    foreach ($insert_columns as $insert_column){
        $sql_insert_columns[]='`'.$insert_column.'`';
    }
    $sql_insert_columns=implode(',',$sql_insert_columns);
    //数据分页
    $num=100;
    $page_values=[];
    foreach ($values as $k=>$value){
        $p=ceil(($k+1)/$num);
        $temp_values=[];
        foreach ($insert_columns as $insert_column){
            $temp=isset($value[$insert_column]) && !is_null($value[$insert_column])?(string)$value[$insert_column]:null;
            $temp_values[]="'".$temp."'";
        }
        $temp_values=implode(',',$temp_values);
        $page_values[$p][]='('.$temp_values.')';
    }
    //更新字段
    if(is_string($update_columns)){
        $sql_update_columns= ' `'.$update_columns.'` = values(`'.$update_columns.'`) ';
    }else{
        $sql_update_columns=[];
        foreach ($update_columns as $update_column){
            $sql_update_columns[]= ' `'.$update_column.'` = values(`'.$update_column.'`) ';
        }
        $sql_update_columns=implode(',',$sql_update_columns);
    }
    // 生成sql
    $sqls=[];
    foreach($page_values as $p=>$value){
        $sql_values=implode(',',$value);
        $sqls[]='insert into `'.$table.'` ('.$sql_insert_columns.') values '.$sql_values.' on duplicate key update '.$sql_update_columns;
    }

    return $sqls;
}

/** 批量更新数据 sql
 * @param string $table         数据表名
 * @param array $insert_columns 数据字段
 * @param array $values         原始数据
 * @param array|string $update_columns  更新字段
 * @param array|string $where_columns   条件字段
 * @return bool|string          返回false(条件不符)，返回string(sql语句)
 */
function batch_update_sql($table='', $insert_columns=[], $values=[], $update_columns=[], $where_columns='id'){
    if(empty($table) || empty($insert_columns) || empty($values) || empty($update_columns) || empty($where_columns)){
        return false;
    }
    // 数据字段必须包含更新字段
    if(is_string($update_columns)){
        if(!in_array($update_columns,$insert_columns)){
            return false;
        }
    }else{
        $common_columns= array_intersect($insert_columns,$update_columns);
        sort($common_columns);
        sort($update_columns);
        if($common_columns != $update_columns){
            return false;
        }
    }
    // 数据字段必须包含条件字段
    if(is_string($where_columns)){
        if(!in_array($where_columns,$insert_columns)){
            return false;
        }
    }else{
        $common_columns= array_intersect($insert_columns,$where_columns);
        sort($common_columns);
        sort($where_columns);
        if($common_columns != $where_columns){
            return false;
        }
    }

    //数据字段
    $sql_insert_columns=[];
    foreach ($insert_columns as $insert_column){
        $sql_insert_columns[]='`'.$insert_column.'`';
    }
    $sql_insert_columns=implode(',',$sql_insert_columns);

    /* ++++++++++ 创建虚拟表 ++++++++++ */
    //创建虚拟表 表名
    $temp_table='`'.$table.'_temp`';
    //创建虚拟表 sql
    $sqls[]='create temporary table '.$temp_table.' as ( select '.$sql_insert_columns.' from `'.$table.'` where 1<>1 )';

    /* ++++++++++ 添加数据 ++++++++++ */
    //数据分页
    $num=100;
    $page_values=[];
    foreach ($values as $k=>$value){
        $p=ceil(($k+1)/$num);
        $temp_values=[];
        foreach ($insert_columns as $insert_column){
            $temp=isset($value[$insert_column]) && !is_null($value[$insert_column])?(string)$value[$insert_column]:null;
            $temp_values[]="'".$temp."'";
        }
        $temp_values=implode(',',$temp_values);
        $page_values[$p][]='('.$temp_values.')';
    }

    //插入数据 sql
    foreach($page_values as $p=>$value){
        $sql_values=implode(',',$value);
        $sqls[]='insert into '.$temp_table.' ('.$sql_insert_columns.') values '.$sql_values;
    }


    /* ++++++++++ 批量更新 ++++++++++ */
    //更新字段
    if(is_string($update_columns)){
        $sql_update_columns= '`'.$table.'`.`'.$update_columns.'`='.$temp_table.'.`'.$update_columns.'`';
    }else{
        $sql_update_columns=[];
        foreach ($update_columns as $update_column){
            $sql_update_columns[]= '`'.$table.'`.`'.$update_column.'`='.$temp_table.'.`'.$update_column.'`';
        }
        $sql_update_columns=implode(',',$sql_update_columns);
    }
    //条件字段
    if(is_string($where_columns)){
        $sql_where_columns= '`'.$table.'`.`'.$where_columns.'`='.$temp_table.'.`'.$where_columns.'`';
    }else{
        $sql_where_columns=[];
        foreach ($where_columns as $where_column){
            $sql_where_columns[]= '`'.$table.'`.`'.$where_column.'`='.$temp_table.'.`'.$where_column.'`';
        }
        $sql_where_columns=implode(' and ',$sql_where_columns);
    }
    //更新数据 sql
    $sqls[]='update `'.$table.'`,'.$temp_table.' set '.$sql_update_columns.' where '.$sql_where_columns;

    return $sqls;
}

/** 生成GUID
 * @return string
 */
function create_guid(){
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $guid = substr($charid, 6, 2).substr($charid, 4, 2).
        substr($charid, 2, 2).substr($charid, 0, 2).$hyphen
        .substr($charid, 10, 2).substr($charid, 8, 2).$hyphen
        .substr($charid,14, 2).substr($charid,12, 2).$hyphen
        .substr($charid,16, 4).$hyphen.substr($charid,20,12);
    return $guid;
}
/** 数组转xls格式的excel文件
 * @param  array  $data      需要生成excel文件的数组
 * @param  string $filename  生成的excel文件名
 *      示例数据：
$data = array(
array(NULL, 2010, 2011, 2012),
array('Q1',   12,   15,   21),
array('Q2',   56,   73,   86),
array('Q3',   52,   61,   69),
array('Q4',   30,   32,    0),
);
 */
function create_housetitle_xls($data,$filename='simple.xls'){

    ini_set('max_execution_time', '0');
    vendor("PHPExcels.PHPExcel");
    $filename=str_replace('.xls', '', $filename).'.xls';
    $filename = iconv("utf-8", "gb2312", $filename);
    $phpexcel = new \PHPExcel();
    $phpexcel->getProperties()
        ->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");

    // 设置个表格宽度
    $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
    $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
    $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(18);
    $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
    $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(16);

    // 水平居中（位置很重要，建议在最初始位置）
    $phpexcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

//    设置单元格的值
    $phpexcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    ob_end_clean();
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=$filename");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
    exit;
}

function create_houses_xls($data,$filename='simple.xls'){

    ini_set('max_execution_time', '0');
    vendor("PHPExcels.PHPExcel");
    $filename=str_replace('.xls', '', $filename).'.xls';
    $filename = iconv("utf-8", "gb2312", $filename);
    $phpexcel = new \PHPExcel();
    $phpexcel->getProperties()
        ->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");

    // 设置个表格宽度
    $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);
    $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(8);
    $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);
    $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(8);
    $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(18);
    $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('L')->setWidth(24);
    $phpexcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('N')->setWidth(46);
    $phpexcel->getActiveSheet()->getColumnDimension('O')->setWidth(40);
    $phpexcel->getActiveSheet()->getColumnDimension('P')->setWidth(40);
    $phpexcel->getActiveSheet()->getColumnDimension('Q')->setWidth(40);

    // 水平居中（位置很重要，建议在最初始位置）
    $phpexcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('N')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('O')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('P')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('Q')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    //    设置单元格的值
    $phpexcel->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('E')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('F')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('H')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('I')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('J')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('K')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('L')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('M')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('N')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('O')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('P')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('Q')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    ob_end_clean();
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=$filename");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
    exit;
}