<?php

namespace qcloudcos;

class CosApi
{

    /**
     * @var int 计算sign签名的时间参数
     */
    private $_expired = 180;

    /**
     * @var int 分片上传时分片大小
     */
    private $_slice_size = 2097152; //2*1024*1024

    /**
     * @var int 大于20M的文件需要分片上传
     */
    private $_unslice_size = 20971520; //20*1024*1024

    /**
     * @var int 最大重试次数
     */
    private $_max_retry = 3;

    /**
     * @var int HTTP请求超时时间
     */
    private $_timeout = 60;

    /**
     * @var string 地区(默认广州)
     */
    private $_region = 'gz';

    private $bucket = '';

    /**
     * 设置HTTP超时时间
     * @param int $time_out 超时时间
     * @return bool
     */
    public function setTimeout($time_out = 60)
    {
        if (is_int($time_out) && $time_out > 0) {
            $this->_timeout = $time_out;
            return true;
        }
        return false;
    }

    /**
     * 设置COS服务地域
     * @param $region
     */
    public function setRegion($region)
    {
        $this->_region = $region;
    }

    /**
     * 构造函数
     * @param $appID string AppId
     * @param $secretID string 身份识别Id
     * @param $secretKey string 身份秘钥
     * @param $bucket string 储存桶
     */
    public function __construct($appID, $secretID, $secretKey, $bucket)
    {
        Conf::$appID = $appID;
        Conf::$secretID = $secretID;
        Conf::$secretKey = $secretKey;
        $this->bucket = $bucket;
    }

    /**
     * 上传文件
     * @param $srcFile string 上传文件路径
     * @param $uploadPath string 保存路径和文件名
     * @param bool $overWrite 是否复写
     * @param string $attribute 属性
     * @return array|mixed
     */
    public function FileUpload($srcFile, $uploadPath, $overWrite = false, $attribute = '')
    {
        if (!file_exists($srcFile)) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => '文件:' . $srcFile . '不存在',
                'data' => array(),
            );
        }

        $uploadPath = $this->normalizerPath($uploadPath, false);

        if (filesize($srcFile) < $this->_unslice_size) {
            return $this->_fileUpload($srcFile, $uploadPath, $overWrite, $attribute);
        } else {
            return $this->_fileUploadBySlicing($srcFile, $uploadPath, $overWrite, $attribute);
        }
    }


    private function _fileUpload($srcFile, $uploadPath, $overWrite, $attribute)
    {
        $srcFile = realpath($srcFile);
        $uploadPath = $this->cosUrlEncode($uploadPath);

        $expired = time() + $this->_expired;
        $url = $this->generateResUrl($uploadPath);
        $signature = Auth::createReusableSignature($expired, $this->bucket);

        $fileSha = hash_file('sha1', $srcFile);

        $data = array(
            'op' => 'upload',
            'sha' => $fileSha,
            'biz_attr' => $attribute,
        );

        $data['filecontent'] = file_get_contents($srcFile);
        $data['insertOnly'] = $overWrite ? '0' : '1';

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => $this->_timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return $this->sendRequest($req);
    }

    private function _fileUploadBySlicing($srcFile, $uploadPath, $overWrite, $attribute)
    {
        $srcFile = realpath($srcFile);
        $fileSize = filesize($srcFile);
        $uploadPath = $this->cosUrlEncode($uploadPath);
        $url = $this->generateResUrl($uploadPath);
        $sliceCount = ceil($fileSize / $this->_slice_size);

        $overWrite = $overWrite ? '0' : '1';

        $expired = time() + ($this->_expired * $sliceCount);

        if ($expired >= (time() + 10 * 24 * 60 * 60)) {
            $expired = time() + 10 * 24 * 60 * 60;
        }

        $signature = Auth::createReusableSignature($expired, $this->bucket);

        $sliceUploading = new SliceUploading($this->_timeout * 1000, $this->_max_retry);

        for ($tryCount = 0; $tryCount < $this->_max_retry; ++$tryCount) {
            if ($sliceUploading->initUploading($signature, $srcFile, $url, $fileSize, $this->_slice_size, $attribute, $overWrite)) {
                break;
            }

            $errorCode = $sliceUploading->getLastErrorCode();
            if ($errorCode === -4019) {
                // Delete broken file and retry again on _ERROR_FILE_NOT_FINISH_UPLOAD error.
                $this->FileDelete($uploadPath);
                continue;
            }

            if ($tryCount === $this->_max_retry - 1) {
                return array(
                    'code' => $sliceUploading->getLastErrorCode(),
                    'message' => $sliceUploading->getLastErrorMessage(),
                    'request_id' => $sliceUploading->getRequestId(),
                );
            }
        }

        if (!$sliceUploading->performUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'request_id' => $sliceUploading->getRequestId(),
            );
        }

        if (!$sliceUploading->finishUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'request_id' => $sliceUploading->getRequestId(),
            );
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'request_id' => $sliceUploading->getRequestId(),
            'data' => array(
                'access_url' => $sliceUploading->getAccessUrl(),
                'resource_path' => $sliceUploading->getResourcePath(),
                'source_url' => $sliceUploading->getSourceUrl(),
            ),
        );
    }

    /**
     * 删除文件
     * @param $file
     * @return array|mixed
     */
    public function FileDelete($file)
    {
        $file = $this->normalizerPath($file);

        if ($file == '/') {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => '不允许删除根节点',
            );
        }

        $file = $this->cosUrlEncode($file);
        $url = $this->generateResUrl($file);
        $signature = Auth::createNonreusableSignature($this->bucket, $file);

        $data = array('op' => 'delete');
        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => $this->_timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            )
        );
        return $this->sendRequest($req);
    }


    private function generateResUrl($dstPath)
    {
        $endPoint = Conf::API_COSAPI_END_POINT;
        $endPoint = str_replace('region', $this->_region, $endPoint);

        return $endPoint . Conf::$appID . '/' . $this->bucket . $dstPath;
    }

    /*
     * 内部方法, 规整文件路径
     * @param  string  $path      文件路径
     * @param  string  $isfolder  是否为文件夹
     */
    private function normalizerPath($path, $isfolder = false)
    {
        if (preg_match('/^\//', $path) == 0) {
            $path = '/' . $path;
        }

        if ($isfolder == True) {
            if (preg_match('/\/$/', $path) == 0) {
                $path = $path . '/';
            }
        }

        // Remove unnecessary slashes.
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /*
     * 内部公共方法, 路径编码
     * @param  string  $path 待编码路径
     */
    private function cosUrlEncode($path)
    {
        return str_replace('%2F', '/', rawurlencode($path));
    }


    private function sendRequest($req)
    {
        $rsp = HttpClient::sendRequest($req);
        if ($rsp === false) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => '网络错误',
            );
        }

        $info = HttpClient::info();
        $ret = json_decode($rsp, true);

        if ($ret === NULL) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => $rsp,
                'data' => array(),
            );
        }
        return $ret;
    }


}