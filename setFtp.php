<?php


class setFtp
{

    /*Библиотеку разрабатывал @lavachik
    Общая стоимость: 440р+ (точно не помню)
    */

    public $ip;
    public $username;
    public $port = 21;
    public $path = '';
    public $timeout = 30;
    public $ftp_pasv = 1;

    public function __construct($ip,$username,$password,$path = '/',$port='21', $timeout=30, $ftp_pasv)
    {
        $this->ip = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->path = $path;
        $this->timeout = $timeout;
        $this->ftp_pasv = $ftp_pasv;

    }



    public function openFtp(){ // в некоторых нужно просто соедения открыть
        error_reporting(0);
            $open_ftp  = ftp_connect($this->ip, $this->port, $this->timeout);

            if(!empty($open_ftp)){

              $FTP_log =   ftp_login($open_ftp, $this->username, $this->password);

              if(!$FTP_log)return false;

            } else {
                return false;
            }


            return $open_ftp;
    }




    public function getFile($remote_file, $loc_file = 'test.txt',$open = null){


        if($open == null){
          $open =  $this->openFtp();
        }

        // открываем файл для записи
        $handle = fopen($loc_file, 'w');

        if ($ftp_pasv == 1)
            ftp_pasv($open, true);

        if (ftp_fget($open, $handle, $this->path.$remote_file, FTP_ASCII, 0)) {


            $data = file_get_contents($loc_file);

            fclose($handle);

            unlink($loc_file);

            return $data;

        } else {
           return false;
        }



    }


    public function creatFile($file_name,$data){
        file_put_contents($file_name,$data);
    }


    public function writeFtp($server_file,$loc_file = 'test.txt',$open = null){

        if($open == null){
            $open =  $this->openFtp();
        }

        $fp = fopen($loc_file, 'r');



// попытка загрузки файла
       // if (ftp_fput($this->openFtp(), $server_file, $fp, FTP_ASCII)) {

        if (ftp_fput($open, $this->path.$server_file, $fp, FTP_ASCII)) {

            // закрываем соединение и дескриптор файла

            ftp_close($open);

            fclose($fp);

            unlink($loc_file);

            return true;
        } else {

            // закрываем соединение и дескриптор файла

            ftp_close($open);

            fclose($fp);

            unlink($loc_file);

            return  false;
        }





    }



    public function getToWriteFTP($server_file,$DATA,$loc_file = 'test.txt',$copy = false){

        $open = $this->openFtp();

        // Подгрузили файл
        $getDataFtp = $this->getFile($server_file,$loc_file,$open);


        if(!empty($copy)) {  //проверка на ПРОВЕРКУ

            if(!empty($getDataFtp)) {  // НЕ Пусто файл ?

                if (trim($getDataFtp) == $DATA) { // ЕСЛИ РАВЕН 1 значению то все
                    return null;
                }


                $getEx = explode(';',$getDataFtp); // ПРобуем делить на части ;
                if(!empty($getEx)) {  // если пустой массив то пропускаем
                    $chars = [';'];   // Делитль строки
                    $data_search = str_replace($chars, '', $DATA); // В строке поиска убраем ;
                   if(in_array($data_search, $getEx)){ // ищем в массиве
                       return null;
                   }

                }

            }

        }

        // Создаем файл готовый
        $this->creatFile($loc_file,$DATA.$getDataFtp);


      return  $this->writeFtp($server_file,$loc_file,$open);

    }




    public function reloadJson(array $data,$json,$key='m_CodeArray'){

        if(!empty($json)){
            $ar_js = json_decode($json,true);

            if(isset($ar_js[$key])){

                $ar_js[$key][] = $data;
                return json_encode($ar_js,JSON_UNESCAPED_UNICODE );

            } else {
                //die("Ошибка, массив поврежден.");
                db_add_error('[setFtp.php]Ошибка, массив поврежден');
            }
        }


    }


    public function recJsonToFtp($server_file,array $data,$loc_file='test.txt'){


        $open = $this->openFtp();

        $getDataFtp = $this->getFile($server_file,$loc_file,$open);


        if(empty($getDataFtp)){

            $DATA = json_encode(['m_CodeArray'=>[$data]],JSON_UNESCAPED_UNICODE );

            // Создаем файл готовый
            $this->creatFile($loc_file,$DATA);
            return  $this->writeFtp($server_file,$loc_file,$open);

            //exit();
        }


        $DATA = self::reloadJson($data,$getDataFtp);

        // Создаем файл готовый
        $this->creatFile($loc_file,$DATA);

       // return $DATA;
        return  $this->writeFtp($server_file,$loc_file,$open);

       // return $getDataFtp;
    }




    public function reloadRedJson ($value,$json,$key){

        if(!empty($json)){
            $ar_js = json_decode($json,true);


            if(isset($ar_js[$key])){

                $ar_js[$key][] = [$value];
                return json_encode($ar_js,JSON_UNESCAPED_UNICODE );

            } else {
                //die("Ошибка, массив поврежден.");
                db_add_error('[setFtp.php]Ошибка, массив поврежден');
            }
        }


    }

    public function RedJson($key,$value,$server_file,$loc_file='test.txt'){

        $open = $this->openFtp();

        $getDataFtp = $this->getFile($server_file,$loc_file,$open);

        $DATA = self::reloadRedJson($value,$getDataFtp,$key);

        // Создаем файл готовый
        $this->creatFile($loc_file,$DATA);

        // return $DATA;
        return  $this->writeFtp($server_file,$loc_file,$open);

    }

    public function redJsonArray($str,$server_file,$plus, $loc_file='test.txt'){

        $open = $this->openFtp();

        // Подгрузили файл
        $getDataFtp = $this->getFile($server_file,$loc_file,$open);

        if(!empty($getDataFtp)){

            $js = json_decode($getDataFtp,true);

            if(isset($js[''.$str.''])){
                $js[''.$str.''] = $js[''.$str.''] + $plus;

                $DATA = json_encode($js,JSON_UNESCAPED_UNICODE );

                // Создаем файл готовый
                $this->creatFile($loc_file,$DATA);


                return  $this->writeFtp($server_file,$loc_file,$open);


            }
        }



    }




}