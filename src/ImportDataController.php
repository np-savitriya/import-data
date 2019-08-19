<?php
/**
 * Created by vsCode.
 * User: ravin
 * Date: 8/13/19
 * Time: 12:23 PM
 */

namespace Import\ImportData;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Import\ImportData\Module;
use Import\ImportData\ImportError;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\User;
use Validator;
use App\Models\Role;
use App\Models\VehicleGroup;
use App\Models\Location;
use App\Models\Asset;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Validation\Validator;


class ImportDataController extends Controller
{
    public static function fetchExcelColumns($request) {

        $response = array();
            // $module = $file['module'];
            $file = $request->file('myFile');
            $mod = $request->input('module');
            $fileName = $request->input('fileName');
            $unitNo = 0;
           $notFoundArr = [];
           $notNeededColumns = ['api_token','password','id','created_at','updated_at','deleted_at','created_by','updated_by','deleted_by','file_path'];
           if(isset($fileName)){
                if (file_exists(base_path('public') . "/exports/".$fileName)) {
                    $file = file_get_contents(base_path('public') . "/exports/".$fileName);
                    
                }
            }
                if ( isset($fileName) || $request->hasFile('myFile') && (strtolower($request->file('myFile')->clientExtension()) == 'xlsx' || strtolower($request->file('myFile')->clientExtension()) == 'xls')) {
                    
                    // $files = Storage::files(base_path('public') . "/exports/");
                    
                    if(!isset($fileName)){

                        // $path = $file->getRealPath();
                        if (!file_exists(base_path('public') . "/exports")) {
                            mkdir(base_path('public') . "/exports", 0777, true);
                        }
                            // if ( strtolower($file->clientExtension()) == 'xls' ) {
                    
                            //     $fileType = \PHPExcel_IOFactory::identify($path);
                                
                            //     $objReader = \PHPExcel_IOFactory::createReader($fileType);
                            //     $objReader->setReadDataOnly(true);
                            //     $objPHPExcel = $objReader->load($path);

                            //     //if file exist delete it
                            //     if (file_exists(storage_path().'/'.$mod.'xlsx')) unlink(storage_path().'/'.$mod.'xlsx');

                            //     $writer = \PHPExcel_IOFactory::createWriter($objPHPExcel,"Excel2007");
                            //     $writer->save( storage_path().'/'.$mod.'xlsx');
                            //     $path = storage_path().'/'.$mod.'xlsx';
                            // }
            
                            $fileName = "";
                            $fileName = $file->getClientOriginalName();
                            $fileName = $mod . "_" . round(microtime(true) * 1000) . "_" . $fileName;
                            $fullPath = $file->move(base_path('public') . '/exports/', $fileName);
                            
                            // return $fileName;
                                $response['code'] = 200;
                                $response["status"] = "success";
                                $response['message'] = 'FileName';
                                $response['content'] = $fileName;
                            
                            return response($response, $response['code'])
                                    ->header('Content_type', 'application/json');
                    }

            $path = base_path('public') . '/exports/'.$fileName;
            $reader = Excel::load($path)->get();

            // $singleRow = $reader->toArray(); // no need to parse whole sheet for the headings
            $headings['headers'] = $reader->getHeading();
            $headArr = [];
            $excelArr = [];
            // return $headings['headers'];
            if(isset($headings['headers'][0])){
                $i = 0;
                foreach($headings['headers'] as $head){
                    if($head != ''){
                        if(in_array($head,$notNeededColumns)){
                            continue;
                        }
                        // if($head == 'id' || $head == 'created_by' || $head == 'updated_by' || $head == 'deleted_at' || $head == 'deleted_by' || $head == 'created_at' || $head == 'updated_at' || $head == 'api_token' || $head == 'password'){
                        //     continue;
                        // }
                        $headArr['text'] = str_replace('_',' ',ucFirst($head));
                        $headArr['value'] = $head;
                        $i++;
                        array_push($excelArr,$headArr);
                    }
                    
                }
                
            }
                // return $reader;
            
            if($mod == 'User'){
                $mod = "App"."\\".$mod;
            }else{
                $mod = "App\\Models\\".$mod;
            }
                $mod = new $mod;
                $table = $mod->getTable();

                $columns = DB::select( DB::raw('SHOW COLUMNS FROM `'.$table.'`'));
                $param = [];
                $fieldArr = [];
                
                $i = 0;
                $j = 0;
                
                foreach($columns as $column) {
                    if($column->Field == 'id' || $column->Field == 'created_by' || $column->Field == 'updated_by' || $column->Field == 'deleted_at' || $column->Field == 'deleted_by' || $column->Field == 'created_at' || $column->Field == 'updated_at' || $column->Field == 'password' || $column->Field == 'api_token'){
                        continue;
                    }
                    $col = explode('_',$column->Field);
                    if(isset($col[2]) && $col[2] == 'id'){
                        $column->Field = $col[0].'_'.$col[1].'_name';
                    }else if(isset($col[1]) && $col[1] == 'id'){
                        $column->Field = $col[0].'_name';
                    }
                    if($column->Null == 'NO'){
                        $param['table_fields'][$i]['name'] = str_replace('_',' ',ucFirst($column->Field));
                        $param['table_fields'][$i]['value'] = $column->Field;
                        $param['table_fields'][$i]['type'] = 'required';
                        $param['table_fields'][$i]['default'] = $column->Default;
                        // $i++;
                    }else{
                        $param['table_fields'][$i]['name'] = str_replace('_',' ',ucFirst($column->Field));
                        $param['table_fields'][$i]['value'] = $column->Field;
                        $param['table_fields'][$i]['type'] = 'optional';
                        $param['table_fields'][$i]['default'] = $column->Default;
                    }
                    $i++;
                }
                if(isset($param)){
                    $selectArr = [];
                    foreach($param['table_fields'] as $pm){
                        // return $pm;
                        if(isset($headings['headers'][0])){
                            $i = 0;
                            foreach($headings['headers'] as $head){
                                // return $pm;
                                if($pm['value'] == $head){
                                    // return 'hello';
                                    $selectArr[$i]['text'] = str_replace('_',' ',ucFirst($head));
                                    $selectArr[$i]['value'] = $head;
                                }
                                $i++;
                                // array_push($excelArr,$headArr);

                            }
                            
                        }
                    }
                }
                // array_push($fieldArr,$param);
                
            
                if ( isset($fieldArr) ) {
                    $response['code'] = 200;
                    $response['message'] = 'Reading imported';
                    $response["status"] = "success";
                    $response["content"] = $param;
                    $response['excel'] = $excelArr;
                    $response['selectedArr'] = $selectArr;
                } else {
                    $response['code'] = 201;
                    $response['message'] = 'Make Sure The Sheet is for Relavant Module';
                    $response["status"] = "success";
                    $response["content"] = '';
                }
           
            } else {
                
                $response['code'] = 400;
                $response["status"] = "error";
                $response['message'] = 'Please Select Excel File';
                $response['content'] = "";
            }
            return response($response, $response['code'])
                    ->header('Content_type', 'application/json');
    }
    public static function fetchModules() {
        $r_param = array();
        $response = array();
        $moduleArr = [];

        $result = Module::where('import_enabled','yes')->get();
        if(isset($result[0])){
            $i = 0;
            foreach($result as $res){
                $resName = explode(' ',$res->name);
                if(isset($resName[2])){
                    if($resName[0] == 'vehicle'){
                        $resName[0] = 'asset';
                    }
                    $res->name = ucFirst($resName[0]).ucFirst($resName[1]).ucFirst($resName[2]);
                }else if(isset($resName[1])){
                    if($resName[0] == 'vehicle'){
                        $resName[0] = 'asset';
                    }
                    $res->name = ucFirst($resName[0]).ucFirst($resName[1]);
                }
                if($res->name == 'vehicle'){
                    $res->name = 'asset';
                }
                $r_param['text'] = ucFirst($res->name);
                $r_param['value'] = $res->id;
                $i++;
                array_push($moduleArr,$r_param);
            }
        }

        if (isset($moduleArr)) {
           return $moduleArr;
        } else {
            return false;
        }
        return response($response, $response['code'])
            ->header('content_type', 'application/json');
    }
    public static function getColumnNames($module){
        
        // $columns = DB::getSchemaBuilder()->getColumnListing($module);
        if($module == 'invoices'){

        }
        $columns = DB::select( DB::raw('SHOW COLUMNS FROM `'.$module.'`'));
        $param = [];
        $fieldArr = [];
        $requiredArr = [];
        $optional = [];
        $i = 0;
        $j = 0;
        foreach($columns as $column) {
            if($column->Null == 'NO'){
                $param['required'][$i]['name'] = $column->Field;
                $param['required'][$i]['type'] = $column->Type;
                $param['required'][$i]['default'] = $column->Default;
                $i++;
            }else{
                $param['optional'][$j]['name'] = $column->Field;
                $param['optional'][$j]['type'] = $column->Type;
                $param['optional'][$j]['default'] = $column->Default;
                $j++;
            }
        }
        array_push($fieldArr,$param);


        return $fieldArr;
    }
    public static function getColumns($table,$module){
        
        // $columns = DB::getSchemaBuilder()->getColumnListing($module);
        // if($module == 'invoices'){

        // }
        $columns = DB::select( DB::raw('SHOW COLUMNS FROM `'.$table.'`'));
        $param = [];
        $fieldArr = [];
        $i = 0;
        
        foreach($columns as $column) {
            if($column->Field == 'id' || $column->Field == 'created_by' || $column->Field == 'updated_by' || $column->Field == 'deleted_at' || $column->Field == 'deleted_by' || $column->Field == 'created_at' || $column->Field == 'updated_at' || $column->Field == 'api_token' || $column->Field == 'password'){
                continue;
            }
            $col = explode('_',$column->Field);
            if(isset($col[2]) && $col[2] == 'id'){
                $column->Field = $col[0].'_'.$col[1].'_name';
            }else if(isset($col[1]) && $col[1] == 'id'){
                $column->Field = $col[0].'_name';
            }
            $param[$column->Field] = '';
            
            $i++;
        }
        array_push($fieldArr,$param);

        $data = $fieldArr;
        $fileName = $module;
        $path = rtrim(app()->basePath('public/'), '/') . '/exports';

        $excel = Excel::create($fileName, function ($excel) use ($data) {
            $excel->sheet('mySheet', function ($sheet) use ($data) {
                $sheet->fromArray($data);
            });
        })->store("xlsx", $path, true);
        chmod($excel['full'], 0777);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $excel['web'] = $protocol . $domainName . '/api/exports/' . $fileName.'.xlsx';

        $response['code'] = 200;
        $response["status"] = "success";
        $response['message'] = 'success export';
        $response['content'] = $excel;

        return $response['content'];
    }

    
    public static function importData($request) {

        $response = array();
            // $module = $file['module'];
            $mod = $request->input('module');
            $fileName = $request->input('fileName');
            $dataArr = $request->input('fields');
            $excelArr = $request->input('Excelfields');
            $selectedArr = $request->input('selectedfields');
            $unitNo = 0;

            $selectArr = json_decode($selectedArr);
            // return $selectArr;
           $notFoundArr = [];
           if(isset($fileName)){
                if (file_exists(base_path('public') . "/exports/".$fileName)) {
                    $file = file_get_contents(base_path('public') . "/exports/".$fileName);
                    
                }
            }
            $moduleId = Module::where('name',$mod)->first();
            if($mod == 'User'){
                $mod = "App"."\\".$mod;
            }else{
                $mod = "App\\Models\\".$mod;
            }
                
            if ( isset($fileName) || $request->hasFile('myFile') && (strtolower($request->file('myFile')->clientExtension()) == 'xlsx' || strtolower($request->file('myFile')->clientExtension()) == 'xls')) {
               
                $path = base_path('public') . '/exports/'.$fileName;
                $reader = Excel::load($path)->get();

                // $singleRow = $reader->toArray(); // no need to parse whole sheet for the headings
                // $headings['headers'] = $reader->getHeading();
                $headArr = [];
                $excelArr = [];
                $mapArr = [];

                if(isset($dataArr)){
                    foreach(json_decode($dataArr) as $data){
                        // return $data;
                        // foreach($data as $dt){
                            $s_param['text'] = $data->name;
                            if(is_object($data->value)){
                                $s_param['value'] = $data->value->value;
                            }else{
                                $s_param['value'] = $data->value;
                            }
                            array_push($mapArr,$s_param);
                        // }
                    }
                }
                $testArr = [];
                $colArr = [];
                if(isset($reader)){
                    // return $reader;
                    $i = 0;
                    $m = 0;
                    $errorCount = 0;
                    foreach($reader as $head){
                        $mod = new $mod;
                        // return $head;
                        $key = key($head->toArray());
                        // return json_decode($dataArr);
                        $j = 0;
                        foreach(json_decode($dataArr) as $data){
                            $arr = $head->toArray();
                        //    return $arr;

                           if($data->type == 'required'){
                               $validator = Validator::make($arr, [
                                $data->value => 'required',
                               ]);
                                $ErrArr = [];
                                if ($validator->fails()) {
                                    $errors = $validator->errors()->toArray();
                                    $er_arr = [];
                                    if(isset($errors)){
                                        
                                        foreach($errors as  $er){
                                            $i = 0;
                                            foreach($er as $e){
                                                $colArr[$m]['name'] = key($errors); 
                                                $er_arr['field'] = key($errors);
                                                $er_arr['error'] =  $e;
                                                array_push($ErrArr,$er_arr);
                                                $i++;
                                                $m++;
                                            }
                                            $errorCount++;
                                        }
                                    }
                                }
                                $error = new ImportError();
                                $error->module_id = $moduleId->id;
                                $error->error_reason = json_encode($ErrArr); 
                                $error->fields = json_encode($colArr);
                                $error->save();
                           }
                            if(!is_object($selectArr[$j]->text)){
                                if($selectArr[$j]->value == 'location_name'){
                                    $locId = Location::where('company_name',$head[$selectArr[$j]->value])->orWhere('code',$head[$selectArr[$j]->value])->pluck('id')->first();
                                    $mod->location_id = $locId;
                                }else{
                                    $column = $data->value;
                                    $value = $selectArr[$j]->value;
                                    $mod->$column = $head[$value];
                                }
                            }else{
                                if($selectArr[$j]->text->value == 'location_name'){
                                    $locId = Location::where('company_name',$head[$selectArr[$j]->text->value])->orWhere('code',$head[$selectArr[$j]->text->value])->pluck('id')->first();
                                    $mod->location_id = $locId;
                                }else{
                                    $column = $data->value;
                                    $mod->$column = $head[$selectArr[$j]->text->value];
                                }
                            }
                        $j++;
                       }
                       $result = $mod->save();
                    }
                }
                if ( isset($result) ) {
                    $response['code'] = 200;
                    $response['message'] = 'Data imported';
                    $response["status"] = "success";
                    $response["content"] = $result;
                    $response['error_count'] = $errorCount;
                } else {
                    $response['code'] = 201;
                    $response['message'] = 'Make Sure The Sheet is for Relavant Module';
                    $response["status"] = "success";
                    $response["content"] = '';
                }
           
            } else {
                
                $response['code'] = 400;
                $response["status"] = "error";
                $response['message'] = 'Please Select Excel File';
                $response['content'] = "";
            }
        
            return response($response, $response['code'])
                    ->header('Content_type', 'application/json');
    }
}