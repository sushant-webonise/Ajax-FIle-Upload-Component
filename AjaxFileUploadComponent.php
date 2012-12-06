<?php

/**
 * Class that encapsulates the file-upload internals
 */
class AjaxFileUploadComponent extends Object
{
    var $controller = '';

    public function initialize($controller) {}

    public function startup() { }

    public function beforeRender() { }

    public function shutdown() { }

    public function beforeRedirect() { }

    private $allowedExtensions;
    private $sizeLimit;
    private $file;
    private $uploadName;

    /**
     * uploads files to the server
     * @param $folder =  the folder to upload the files e.g. 'img/files'
     * @param array $permitted; defaults to an empty array
     * @param int $sizeLimit; defaults to the server's upload_max_filesize setting
     *
     * @return json response
     */

    function uploadFiles($folder, $permitted, $sizeLimit = null, $replaceOldFile= FALSE)
    {

        // setup dir names absolute and relative
        $folder_url = WWW_ROOT . $folder;
        $rel_url = $folder_url;

        // create the folder if it does not exist
        if (!is_dir($folder)) {
            mkdir($folder_url, 0777, true);

            //change the chmod of medium project directory
            chmod($folder_url, 0777);
        }

        if($sizeLimit===null) {
            $sizeLimit = $this->toBytes(ini_get('upload_max_filesize'));
        }

        $allowedExtensions = array_map("strtolower", $permitted);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        $this->checkServerSettings();

        if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') === 0) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = new qqUploadedFileXhr();
        }
        if (!is_writable($folder_url)){
            return json_encode(array('error' => "Server error. Upload directory isn't writable."));
        }

        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }

        $size = $this->file->getSize();

        if ($size == 0) {
            return json_encode(array('error' => 'File is empty'));
        }

        if ($size > $this->sizeLimit) {
            return json_encode(array('error' => 'File is too large'));
        }

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = @$pathinfo['extension'];		// hide notices if extension is empty

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return json_encode(array('error' => 'File has an invalid extension, it should be one of '. $these . '.'));
        }

        $ext = ($ext == '') ? $ext : '.' . $ext;

        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($folder_url . DIRECTORY_SEPARATOR . $filename . $ext)) {
//                $filename .= rand(10, 99);
                ini_set('date.timezone', 'Europe/London');
                $now = strtotime("now") . "_";
                $filename = $now . $filename;
            }
        }

        $this->uploadName = $filename . $ext;

        if ($this->file->save($folder_url . DIRECTORY_SEPARATOR . $filename . $ext)){
            return json_encode(array('success' => array(true)));
        } else {
            return json_encode(array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered'));
        }

    }

    /**
     * Get the name of the uploaded file
     * @return string
     */
    public function getUploadName(){
        if( isset( $this->uploadName ) )
            return $this->uploadName;
    }

    /**
     * Get the original filename
     * @return string filename
     */
    public function getName(){
        if ($this->file)
            return $this->file->getName();
    }

    /**
     * Internal function that checks if server's may sizes match the
     * object's maximum size for uploads
     */
    private function checkServerSettings(){
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
    }

    /**
     * Convert a given size with units to bytes
     * @param string $str
     */
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    public function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()){
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

    /**
     * Get the original filename
     * @return string filename
     */
    public function getName() {
        return $_GET['qqfile'];
    }

    /**
     * Get the file size
     * @return integer file-size in byte
     */
    public function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {

    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    public function save($path) {
        return move_uploaded_file($_FILES['qqfile']['tmp_name'], $path);
    }

    /**
     * Get the original filename
     * @return string filename
     */
    public function getName() {
        return $_FILES['qqfile']['name'];
    }

    /**
     * Get the file size
     * @return integer file-size in byte
     */
    public function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

