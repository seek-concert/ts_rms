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
/** 导出xls格式的excel文件
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
function export_house_xls($data,$filename='simple.xls'){

    ini_set('max_execution_time', '0');
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
    $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
    $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
    $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(13);
    $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(14);
    $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);
    $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(50);
    $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(16);

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

//    设置单元格的值
    $phpexcel->getActiveSheet()->getStyle('F')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
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

function house_import_demo_xls($data,$filename='simple.xls'){
    ini_set('max_execution_time', '0');
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
    $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
    $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(16);
    $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(18);
    $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('L')->setWidth(26);
    $phpexcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
    $phpexcel->getActiveSheet()->getColumnDimension('N')->setWidth(30);
    $phpexcel->getActiveSheet()->getColumnDimension('O')->setWidth(30);
    $phpexcel->getActiveSheet()->getColumnDimension('P')->setWidth(35);
    $phpexcel->getActiveSheet()->getColumnDimension('Q')->setWidth(35);
    $phpexcel->getActiveSheet()->getColumnDimension('R')->setWidth(25);
    $phpexcel->getActiveSheet()->getColumnDimension('S')->setWidth(25);
    $phpexcel->getActiveSheet()->getColumnDimension('T')->setWidth(35);
    $phpexcel->getActiveSheet()->getColumnDimension('U')->setWidth(38);
    $phpexcel->getActiveSheet()->getColumnDimension('V')->setWidth(38);

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
    $phpexcel->setActiveSheetIndex(0)->getStyle('R')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('S')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('T')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('U')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $phpexcel->setActiveSheetIndex(0)->getStyle('V')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    //    设置单元格的值
    $phpexcel->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('E')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('F')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('H')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $phpexcel->getActiveSheet()->getStyle('I')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('J')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('K')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('L')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('M')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('N')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('O')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('P')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('Q')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('R')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $phpexcel->getActiveSheet()->getStyle('S')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $phpexcel->getActiveSheet()->getStyle('T')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $phpexcel->getActiveSheet()->getStyle('U')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $phpexcel->getActiveSheet()->getStyle('V')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

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

/**
 * 导入excel文件
 * @param  string $file excel文件路径
 * @return array        excel文件内容数组
 * data_count     总条数
 * success_count  成功条数
 * error_count    失败条数
 * add_count      可添加条数
 * add_datas      可添加数组
 */
function import_house($file)
{
    // 判断文件是什么格式
    $type = pathinfo($file);
    $type = strtolower($type["extension"]);
    $type = $type === 'csv' ? $type : 'Excel5';
    ini_set('max_execution_time', '0');
    // 判断使用哪种格式
    $objReader = PHPExcel_IOFactory::createReader($type);
    $objPHPExcel = $objReader->load($file, $encode = 'utf-8');
    $sheet = $objPHPExcel->getSheet(0);
    // 取得总行数
    $highestRow = $sheet->getHighestRow();
    // 取得总列数
    $highestColumn = $sheet->getHighestColumn();
    //循环读取excel文件
    $data = array();
    /* 获取表头数组*/
    $title = [
        'company_id' => '管理机构',
        'community_id' => '房源社区',
        'layout_id' => '户型',
        'building' => '楼栋',
        'unit' => '单元',
        'floor' => '楼层',
        'number' => '房号',
        'area' => '面积(㎡)',
        'total_floor' => '总楼层',
        'delive_at' => '交付时间(年月日)',
        'lift' => '是否有电梯',
        'is_real' => '是否现房',
        'is_buy' => '是否购置房',
        'is_transit' => '是否可作临时周转',
        'is_public' => '是否可项目共享',
        'start_at_a' => '房源评估开始时间(年月日)',
        'end_at_a' => '房源评估结束时间(年月日)',
        'market' => '评估市场价',
        'price' => '安置优惠价',
        'manage_price' => '购置管理费单价(元/月)',
        'start_at' => '购置管理费单价开始时间(年)',
        'end_at' => '购置管理费单价结束时间(年)'
    ];

    /*数据拼装*/
    $keys_array = [];
    //从第二行开始读取数据
    for ($j = 2; $j <= $highestRow; $j++) {
        //从A列读取数据
        for ($k = 'A'; $k <= $highestColumn; $k++) {
            // 读取单元格
            $vals = $objPHPExcel->getActiveSheet()->getCell($k . '1')->getValue();
            $keys = array_search($vals, $title);
            $cell = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            // 转字符型
            if ($cell instanceof PHPExcel_RichText) {
                $cell = $cell->__toString();
            }
            $data[$j][$keys] = $cell;
        }
        // 数据验证及查询
        /*管理机构*/
        if (isset($data[$j]['company_id'])) {
            $company_id = \App\Http\Model\Housecompany::where('name', trim($data[$j]['company_id']))->value('id');
            if (isset($company_id)) {
                $data[$j]['company_id'] = $company_id;
            } else {
                $keys_array[] = $j;
                continue;
            }
        } else {
            $keys_array[] = $j;
            continue;
        }

        /*管理社区*/
        if (isset($data[$j]['community_id'])) {
            $community_id = \App\Http\Model\Housecommunity::where('name', trim($data[$j]['community_id']))->value('id');
            if (isset($community_id)) {
                $data[$j]['community_id'] = $community_id;
            } else {
                $keys_array[] = $j;
                continue;
            }
        } else {
            $keys_array[] = $j;
            continue;
        }
        /*户型ID 户型图ID*/
        if (isset($data[$j]['layout_id'])) {
            $layout_id = \App\Http\Model\Layout::where('name', trim($data[$j]['layout_id']))->value('id');
            $layout_img_id = \App\Http\Model\Houselayoutimg::where('layout_id', $layout_id)
                ->where('community_id', $community_id)
                ->value('id');
            if (isset($layout_id) && isset($layout_img_id)) {
                $data[$j]['layout_id'] = $layout_id;
                $data[$j]['layout_img_id'] = $layout_img_id;
            } else {
                $keys_array[] = $j;
                continue;
            }
        } else {
            $keys_array[] = $j;
            continue;
        }

        /*楼栋*/
        if (empty($data[$j]['building'])) {
            $data[$j]['building'] = '';
        }
        /*单元*/
        if (empty($data[$j]['unit'])) {
            $data[$j]['unit'] = '';
        } elseif (isset($data[$j]['unit']) && !is_numeric($data[$j]['unit'])) {
            $keys_array[] = $j;
            continue;
        }
        /*楼层*/
        if (empty($data[$j]['floor'])) {
            $data[$j]['floor'] = '';
        } else if (isset($data[$j]['floor']) && !is_numeric($data[$j]['floor'])) {
            $keys_array[] = $j;
            continue;
        }
        /*房号*/
        if (empty($data[$j]['number'])) {
            $data[$j]['floor'] = '';
        }

        /*面积*/
        if (empty($data[$j]['area'])) {
            $keys_array[] = $j;
            continue;
        } elseif (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', trim($data[$j]['area'])) == false) {
            $keys_array[] = $j;
            continue;
        }

        /*总楼层*/
        if (empty($data[$j]['total_floor'])) {
            $keys_array[] = $j;
            continue;
        }


        /*是否有电梯*/
        if (isset($data[$j]['lift']) && !in_array(trim($data[$j]['lift']), ['是', '否'])) {
            $keys_array[] = $j;
            continue;
        }
        if(trim($data[$j]['lift']=='是')){
            $data[$j]['lift'] = 1;
        }else{
            $data[$j]['lift'] = 0;
        }
        /*是否现房*/
        if (isset($data[$j]['is_real']) && !in_array(trim($data[$j]['is_real']), ['现房', '期房'])) {
            $keys_array[] = $j;
            continue;
        }
        if(trim($data[$j]['is_real']=='现房')){
            $data[$j]['is_real'] = 1;
        }else{
            $data[$j]['is_real'] = 0;
        }
        /*是否购置房*/
        if (isset($data[$j]['is_buy']) && !in_array(trim($data[$j]['is_buy']), ['是', '否'])) {
            $keys_array[] = $j;
            continue;
        }
        if(trim($data[$j]['is_buy']=='是')){
            $data[$j]['is_buy'] = 1;
        }else{
            $data[$j]['is_buy'] = 0;
        }
        /*是否可过渡*/
        if (isset($data[$j]['is_transit']) && !in_array(trim($data[$j]['is_transit']), ['是', '否'])) {
            $keys_array[] = $j;
            continue;
        }
        if(trim($data[$j]['is_transit']=='是')){
            $data[$j]['is_transit'] = 1;
        }else{
            $data[$j]['is_transit'] = 0;
        }
        /*是否项目专用*/
        if (isset($data[$j]['is_public']) && !in_array(trim($data[$j]['is_public']), ['是', '否'])) {
            $keys_array[] = $j;
            continue;
        }
        if(trim($data[$j]['is_public']=='是')){
            $data[$j]['is_public'] = 1;
        }else{
            $data[$j]['is_public'] = 0;
        }
        /*交付时间*/
        if (trim($data[$j]['is_buy']) == '1') {
            if (isset($data[$j]['delive_at'])) {
                $delive_at = strtotime(trim($data[$j]['delive_at']));
                if ($delive_at == false) {
                    $keys_array[] = $j;
                    continue;
                }
            }
        } else {
            $data[$j]['delive_at'] = null;
        }

        $data[$j]['picture'] = [];
        $data[$j]['code'] = 150;
        /*========评估单价=============*/
        /*开始时间*/
        if (isset($data[$j]['start_at_a'])) {
            $start_at_a = strtotime(trim($data[$j]['start_at_a']));
            if ($start_at_a == false) {
                $keys_array[] = $j;
                continue;
            }
        } else {
            $keys_array[] = $j;
            continue;
        }
        /*结束时间*/
        if (isset($data[$j]['end_at_a'])) {
            $end_at_a = strtotime(trim($data[$j]['end_at_a']));
            if ($end_at_a == false) {
                $keys_array[] = $j;
                continue;
            }
        } else {
            $keys_array[] = $j;
            continue;
        }
        /*市场评估价*/
        if (empty($data[$j]['market'])) {
            $keys_array[] = $j;
            continue;
        } elseif (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', trim($data[$j]['market'])) == false) {
            $keys_array[] = $j;
            continue;
        }
        /*安置优惠价*/
        if (empty($data[$j]['price'])) {
            $keys_array[] = $j;
            continue;
        } elseif (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', trim($data[$j]['price'])) == false) {
            $keys_array[] = $j;
            continue;
        }

        /*========购置管理费单价=============*/
        if (trim($data[$j]['is_buy']) == '是') {
            /*开始时间*/
            if (isset($data[$j]['start_at_a'])) {
                $start_at_a = strtotime(trim($data[$j]['start_at_a']));
                if ($start_at_a == false) {
                    $keys_array[] = $j;
                    continue;
                }
            } else {
                $keys_array[] = $j;
                continue;
            }
            /*结束时间*/
            if (isset($data[$j]['end_at_a'])) {
                $end_at_a = strtotime(trim($data[$j]['end_at_a']));
                if ($end_at_a == false) {
                    $keys_array[] = $j;
                    continue;
                }
            } else {
                $keys_array[] = $j;
                continue;
            }
            /*公摊费单价*/
            if (empty($data[$j]['manage_price'])) {
                $keys_array[] = $j;
                continue;
            } elseif (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', trim($data[$j]['manage_price'])) == false) {
                $keys_array[] = $j;
                continue;
            }
        }
    }
        /*
         * data_count     总条数
         * success_count  成功条数
         * error_count    失败条数
         * add_count      可添加条数
         * add_datas      可添加数组*/
        $data_count = count($data);
        $error_count = count($keys_array);
        $success_count = $data_count - $error_count;
        // 去除不符合格式的数据
        if ($error_count > 0) {
            foreach ($keys_array as $k => $v) {
                unset($data[$v]);
            }
        }
        /*符合格式的数据去重复*/
        $data_unique = [];
        foreach ($data as $k => $v) {
            $data_unique[$k] = $v['company_id'] . '|' .$v['community_id'] . '|' . $v['building'] . '|' . $v['unit'] . '|' . $v['floor'] . '|' . $v['number'];
        }
        /*合格数据键名*/
        $add_keys = array_keys(array_unique($data_unique));
        $add_count = count($add_keys);
        $add_datas = [];
        foreach ($add_keys as $k => $v) {
            $add_datas[] = $data[$v];
        };
        $new_data = [
            'data_count' => $data_count,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'add_count' => $add_count,
            'add_datas' => $add_datas
        ];
        return $new_data;
}