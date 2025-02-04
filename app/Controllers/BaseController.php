<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
    }

    public function getPaginationParams($typeReceive = 'post')
    {

        $limit = $this->receiveParameter_by_request_type($typeReceive, 'limit', 10);
        $offset = $this->receiveParameter_by_request_type($typeReceive, 'offset', 0);
        $keyword = $this->receiveParameter_by_request_type($typeReceive, 'keyword');
        $ordersBy = $this->receiveParameter_by_request_type($typeReceive, 'ordersBy', [], 'array');
        $ordersType = $this->receiveParameter_by_request_type($typeReceive, 'ordersType', [], 'array');

        $paginationData = [
            'limit' => $limit,
            'offset' => $offset,
            'keyword' => $keyword,
            'ordersBy' => $ordersBy,
            'ordersType' => $ordersType,
            'keywordColumnList' => [],
            'ordersColumnList' => [],
            'mappingColumnNameFEandBE' => []
        ];

        return (object)$paginationData;
    }

    private function receiveParameter_by_request_type($typeReceive = 'post', $key = '', $defaultValue = null, $typeData = 'string')
    {
        return $typeReceive == 'post' ? $this->receiveParameterPOST($key, $defaultValue, $typeData) : $this->receiveParameterGET($key, $defaultValue, $typeData);
    }

    function receiveParameterPOST($paramName, $defaultReturn = null, $typeData = 'string')
    {
        return $this->receiveParameter($paramName, $defaultReturn, $typeData, 'post');
    }

    function receiveParameterGET($paramName, $defaultReturn = null, $typeData = 'string')
    {
        return $this->receiveParameter($paramName, $defaultReturn, $typeData, 'get');
    }

    private function receiveParameter($paramName, $defaultReturn, $typeData, $typeReceive = 'post' | 'get')
    {
        if ($typeData == 'array') {
            $tempDefaultReturn = $defaultReturn;
            $defaultReturn = null;
        }
        $result = $typeReceive == 'post' ? $this->receivePOST($paramName, $defaultReturn) : $this->receiveGET($paramName, $defaultReturn);

        if ($typeData == 'array') {
            if ($tempDefaultReturn == null) {
                $defaultReturn = [];
            } else {
                $defaultReturn =  $tempDefaultReturn;
            }

            if ($result != null) {
                return explode(',', $result);
            }
            return $defaultReturn;
        }

        return $result;
    }

    private function receivePOST($paramName, $defaultReturn)
    {
        return isset($_POST[$paramName])
            ? ($_POST[$paramName] != ''
                ? $_POST[$paramName]
                : $defaultReturn)
            : $defaultReturn;
    }

    private function receiveGET($paramName, $defaultReturn)
    {
        return isset($_GET[$paramName])
            ? ($_GET[$paramName] != ''
                ? $_GET[$paramName]
                : $defaultReturn)
            : $defaultReturn;
    }

    function mandatoryParam(
        $key,
        $errorDescription = null,
        $type_request = 'get',
        $type = 'text',
        $select_list = [],
        $dateFormat = null,
        $minNumber = 0,
        $maxNumber = 1,
        $extension_list = []
    ) {
        return [
            'key' => $key,
            'errorDescription' =>
            $errorDescription != null
                ? $errorDescription
                : $key . ' must be fill',
            'type_request' => $type_request,
            'type' => $type,
            'select_list' => $select_list,
            'dateFormat' => $dateFormat,
            'minNumber' => $minNumber,
            'maxNumber' => $maxNumber,
            'extension_list' => $extension_list,
        ];
    }

    function checkMandatoryParam($mandatoryParamList)
    {
        foreach ($mandatoryParamList as $mandatoryParam) {
            if ($mandatoryParam['type_request'] == 'file') {
                $data = $this->c($mandatoryParam['key']);
            } else {
                $data =
                    $mandatoryParam['type_request'] == 'get'
                    ? $this->receiveParameterGET($mandatoryParam['key'])
                    : $this->receiveParameterPOST($mandatoryParam['key']);
            }
            if ($data == null) {
                $this->returnJsonError(
                    null,
                    $mandatoryParam['errorDescription']
                );
            }

            if ($mandatoryParam['type'] == 'select') {
                if (!in_array($data, $mandatoryParam['select_list'])) {
                    $this->returnJsonError(
                        null,
                        $mandatoryParam['key'] .
                            ' must in (' .
                            implode(', ', $mandatoryParam['select_list']) .
                            ')'
                    );
                }
            }

            if ($mandatoryParam['type'] == 'date') {
                $dateString = $data;
                $format = $mandatoryParam['dateFormat'];

                $dateTime = DateTime::createFromFormat($format, $dateString);

                if (
                    $dateTime === false ||
                    $dateTime->format($format) !== $dateString
                ) {
                    $this->returnJsonError(
                        null,
                        $mandatoryParam['key'] .
                            ' date format must be ' .
                            $format
                    );
                }
            }

            if ($mandatoryParam['type'] == 'number') {
                $number = (int)$data;
                if (
                    $number < $mandatoryParam['minNumber'] ||
                    $mandatoryParam['maxNumber'] < $number
                ) {
                    $this->returnJsonError(
                        null,
                        $mandatoryParam['key'] .
                            ' number must range in ' .
                            $mandatoryParam['minNumber'] .
                            ' and ' .
                            $mandatoryParam['maxNumber']
                    );
                }
            }

            if ($mandatoryParam['type'] == 'file') {
                if (
                    !in_array(
                        pathinfo($data['name'], PATHINFO_EXTENSION),
                        $mandatoryParam['extension_list']
                    )
                ) {
                    $this->returnJsonError(
                        null,
                        $mandatoryParam['key'] .
                            ' must be in type (' .
                            implode(', ', $mandatoryParam['extension_list']) .
                            ')'
                    );
                }
            }

            if ($mandatoryParam['key'] == 'email') {
                $email = $this->receiveParameterPOST($mandatoryParam['key']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->returnJsonError(null, 'Invalid email format');
                }
            }

            if ($mandatoryParam['type'] == 'longlat') {
                if ($mandatoryParam['key'] == 'longitude' || $mandatoryParam['key'] == 'latitude') {
                    $longitude = null;
                    $latitude = null;

                    if ($mandatoryParam['key'] == 'longitude') {
                        $longitude = floatval($data);

                        if ($longitude < 96 || $longitude > 142) {
                            $this->returnJsonError(null, 'Longitude must be between 96 and 142!');
                        }
                    }

                    if ($mandatoryParam['key'] == 'latitude') {
                        $latitude = floatval($data);

                        if ($latitude < -12 || $latitude > 7) {
                            $this->returnJsonError(null, 'Latitude must be between -12 and 7!');
                        }
                    }
                }
            }
        }
    }


    function returnJson($msg)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($msg);
        exit();
    }

    function returnJsonSuccess(
        $data = null,
        $msg = null,
        array $additional_response = [],
        string $redirect = ''
    ) {
        if ($msg == null) {
            $msg = 'success';
        }
        $responseSucess = [
            'status' => 'ok',
            'msg' => $msg,
        ];
        if (isset($data)) {
            $responseSucess['data'] = $data;
        }

        if (!empty($redirect)) {
            $responseSucess['redirect'] = $redirect;
        }
        $response = array_merge($responseSucess, $additional_response);
        $this->returnJson($response);
    }

    function returnJsonError(
        $data,
        $msg = null,
        array $additional_response = [],
        string $redirect = '',
        int $status_code = 500
    ) {
        http_response_code($status_code);
        if ($msg == null) {
            $msg = 'failed';
        }
        $responseError = [
            'status' => 'error',
            'msg' => $msg,
        ];
        if (isset($data)) {
            $responseError['data'] = $data;
        }
        if (!empty($redirect)) {
            $responseError['redirect'] = $redirect;
        }
        $response = array_merge($responseError, $additional_response);
        // $this->output->set_status_header($status_code)->set_header("X-Custom-Status-Text: $msg")->set_output(json_encode($response));
        //        header("X-Custom-Status-Text: $msg");
        $this->returnJson($response);
    }

    function returnJsonPagination($result)
    {
        $data = isset($result->data) ? $result->data : [];
        $total = isset($result->total_count) ? $result->total_count : 0;

        $meta_data = [
            'searchable_columns' => isset($result->searchColumnList) ? $result->searchColumnList : [],
            'orderable_columns' => isset($result->ordersColumnList) ? $result->ordersColumnList : [],
        ];

        $responsePagination = [
            'status' => 'ok',
            'recordsTotal' => $total,
            'data' => $data,
            'meta_data' => $meta_data
        ];

        $additional_response = isset($result->additional_response) && is_array($result->additional_response) ? $result->additional_response : [];
        $response = array_merge($responsePagination, $additional_response);

        $this->returnJson($response);
    }
}
