<?php
/**
 * Created by vsCode.
 * User: ravin
 * Date: 8/13/19
 * Time: 12:23 PM
 */

namespace Import\ImportData;

use Illuminate\Http\Request;
use Validator;
use Laravel\Lumen\Routing\Controller;
use Import\ImportData\Module;
use Import\ImportData\ImportError;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ImportDataController extends Controller
{
    public function importData($dataArr) {

        $response = array();
            $loggedInuser = $request->input('userId');
            $file = $request->input('myFile');
            $device = $request->input('device');
            $unitNo = 0;
            $userData = User::getUserwithTimezone($loggedInuser);
            $usertimezone = $userData->time_zone;
            if ( !isset($usertimezone) || $usertimezone == '' ) {
                $usertimezone = 'PST8PDT';
            }
           $notFoundArr = [];
           
            if ( $request->hasFile('myFile') && (strtolower($request->file('myFile')->clientExtension()) == 'xlsx' || strtolower($request->file('myFile')->clientExtension()) == 'xls')) {
            $path = $request->file('myFile')->getRealPath();

            if ( strtolower($request->file('myFile')->clientExtension()) == 'xls' ) {
                
                $fileType = \PHPExcel_IOFactory::identify($path);
                
                $objReader = \PHPExcel_IOFactory::createReader($fileType);
                $objReader->setReadDataOnly(true);
                $objPHPExcel = $objReader->load($path);

                //if file exist delete it
                if (file_exists(storage_path().'/export.xlsx')) unlink(storage_path().'/export.xlsx');

                $writer = \PHPExcel_IOFactory::createWriter($objPHPExcel,"Excel2007");
                $writer->save( storage_path().'/export.xlsx');
                $path = storage_path()."/export.xlsx";
            }
            
            $reader = Excel::load($path)->get();

            if (isset($reader[0])) {
                $i = 0;
                foreach ($reader as $ts) {
                    
                    $asset = Asset::where('unit_no',$unitNo)->first();
                    $measurement = MeasurementUnit::where('symbol','MLS')->first();
                    if ( !$measurement ) {

                        $measurement = new MeasurementUnit();
                        $measurement->name = 'Miles';
                        $measurement->symbol = 'MLS';
                        $measurement->created_by = $loggedInuser;
                        $measurement->updated_by = $loggedInuser;
                        $measurement->save();
                    }
                    
                    $section = SectionRate::where(strtolower('code'),'dist')->first();
                    // $convertedTime = TimezoneHelper::ConvertTimezoneToAnotherTimezone($time,'Y-m-d H:i:s','UTC',$usertimezone);
                    if ( isset($asset) ) {
                        
                        $odometerData = AssetOdometerReading::where('vehicle_id',$asset->id)
                                                            ->where('reading_date',$time)
                                                            ->first();
                        
                        if ( !$odometerData ) {

                            $odometerData = new AssetOdometerReading();

                            //convert user timezone to UTC
                            $utcTime = TimezoneHelper::ConvertTimezoneToAnotherTimezone($time,'Y-m-d H:i:s', $usertimezone,'UTC');
                            $odometerData->reading_date = $utcTime;

                            //get customer id for vehicle on reading date with reference to contract
                            $customerData = $this->reading->getCustomerIdFromVehicleIdAndReadingDate($asset->id,$utcTime);

                            $odometerData->reading = $reading;
                            $odometerData->vehicle_id = $asset->id;
                            $odometerData->vom = $measurement->id;
                            $odometerData->meter_change = 0;
                            $odometerData->is_accurate = 0;
                            $odometerData->section_rate_id = $section->id;
                            //if set customer id than update reading with customer id
                            if ( isset($customerData->id)) {
                                $odometerData->customer_id = $customerData->custId;
                            }

                            $odometerData->input_by = 'device';
                            if ( $device == 'platform' ) {
                                $odometerData->device_type = 'Platform Science';
                            } else if($device == 'telogies' ) {
                                $odometerData->device_type = 'Telogies';
                            }
                            $result = $odometerData->save();

                        } else {
                            $result = 1;
                            continue;
                        }
                       
                    } else {
                        $notFoundArr[$i] = $unitNo;
                        $i++;
                    }
                }

            } else {
                $response['code'] = 400;
                $response["status"] = "error";
                $response['message'] = 'Data not found';
                $response['content'] = "";
            }
                if ( isset($result) ) {
                    $response['code'] = 200;
                    $response['message'] = 'Reading imported';
                    $response["status"] = "success";
                    $response["content"] = $notFoundArr;
                } else {
                    $response['code'] = 201;
                    $response['message'] = 'Make sure data is from relevant device';
                    $response["status"] = "success";
                    $response["content"] = $notFoundArr;
                }
           

            } else {
                
                $response['code'] = 400;
                $response["status"] = "error";
                $response['message'] = 'Please Select Excel File';
                $response['content'] = "";

                return response($response, $response['code'])
                    ->header('Content_type', 'application/json');
            }
    }
    public static function fetchModules() {
        $r_param = array();
        $response = array();
        $moduleArr = [];

        $result = Module::all();
        if(isset($result[0])){
            $i = 0;
            foreach($result as $res){
                $r_param['text'] = str_replace(' ','',ucFirst($res->name));
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
}