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
use Module;
use ImportError;

class ImportDataController extends Controller
{
    public function importData($dataArr) {

        if (isset($result)) {
            $response['code'] = 200;
            $response['message'] = 'success';
            $response['content'] = $result;
        } else {
            $response['code'] = 400;
            $response['message'] = 'error';
            $response['content'] = '';
        }
        return response($response, $response['code'])
            ->header('content_type', 'application/json');
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
            }
            array_push($moduleArr,$r_param);
        }

        if (isset($moduleArr)) {
            $response['code'] = 200;
            $response['message'] = 'success';
            $response['content'] = $moduleArr;
        } else {
            $response['code'] = 400;
            $response['message'] = 'error';
            $response['content'] = '';
        }
        return response($response, $response['code'])
            ->header('content_type', 'application/json');
    }
}