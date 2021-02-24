<?php

/**
  класс модуля
 * */

namespace Nyos\mod;

//if (!defined('IN_NYOS_PROJECT'))
//    throw new \Exception('Сработала защита от розовых ха+1керов, обратитесь к администрратору');


class VarsOnSite {

    public static $dir_files = 'vars_files';
    public static $cash_file = 'vars_site.json';

    /**
     * трём кеш
     */
    public static function clearCash() {

        if (file_exists(DR . dir_site . self::$cash_file))
            unlink(DR . dir_site . self::$cash_file);
    }

    public static function getCash() {

        if (file_exists(DR . dir_site . self::$cash_file))
            return json_decode(file_get_contents(DR . dir_site . self::$cash_file), true);

        return false;
    }

    public static function setCash($data_array) {

        file_put_contents(DR . dir_site . self::$cash_file, json_encode($data_array));
    }

    /**
     * добавляем перменную
     * @param type $db
     * @param type $name
     * @param type $var
     * @param type $file
     * @param type $type
     */
    public static function addVar($db, $name, $value, $file = [], $folder = null) {

        self::clearCash();

        $indb = array(
            'name' => $name
            , 'val' => $value
        );

        if (!empty($folder)) {
            $indb['folder'] = $folder;
        }

        $ff = $db->prepare('DELETE FROM `didrive_vars` WHERE ( `folder` = :folder OR `folder` IS NULL ) AND `name` = :name ');
        $ff->execute(array(':folder' => \Nyos\Nyos::$folder_now, ':name' => $indb['name']));
        // $res = $ff->fetchAll();

        if (!empty($file['tmp_name']) && isset($file['size']) && $file['size'] > 0 && isset($file['error']) && $file['error'] == 0) {

            if (!empty($file['name']))
                $indb['val'] = $file['name'];

            $dir_for_file = DR . dir_site_sd . self::$dir_files . DS;

            if (!is_dir($dir_for_file))
                mkdir($dir_for_file, 0755);

            $new_file = date('YmdHIs', $_SERVER['REQUEST_TIME']) . '.' . substr(\f\translit($file['name'], 'uri2'), 0, 50) . '.' . \f\get_file_ext($file['name']);
            //echo $dir_for_file . $new_file;
            copy($file['tmp_name'], $dir_for_file . $new_file);

            $indb['file'] = $new_file;
        }

        // \f\pa($indb);

        \f\db\db2_insert($db, 'didrive_vars', $indb);

//        try{
//            
//            $db -> 
//            
//        } catch (\Exception $ex) {
//
//        }
    }

    /**
     * чистим значение переменной
     * @param type $db
     * @param type $name
     * @param type $folder
     * @return boolean
     */
    public static function deleteVar($db, $name, $folder = null) {

        self::clearCash();

        if (!empty($folder)) {
            $indb['folder'] = $folder;
        }

        $ff = $db->prepare('UPDATE `didrive_vars` SET `val` = \'\', `file` = \'\' WHERE ( `folder` = :folder OR `folder` IS NULL ) AND `name` = :name ');
        $ff->execute(array(':folder' => $folder, ':name' => $name ));

        return true;
    }

    /**
     * получаем все переменные
     * @param type $db
     * @return type
     */
    public static function getVars($db) {

        try {

//                $ff = $db->prepare('DROP TABLE `didrive_vars` ;');
//                $ff->execute();

            $cash = self::getCash();
            if ( isset($cash) && $cash !== false)
                return $cash;

            $ff = $db->prepare('SELECT * FROM `didrive_vars` WHERE `folder` = :folder OR `folder` IS NULL ');
            $ff->execute(array(':folder' => \Nyos\Nyos::$folder_now));
            $res0 = $ff->fetchAll();

            $res = [];

            foreach ($res0 as $k => $v) {
                if (!empty($v['file'])) {
                    $res[$v['name']] = [
                        'val' => $v['val']
                        , 'file' => $v['file']
                        , 'file_link' => dir_site_sd . self::$dir_files . DS . $v['file']
                    ];
                } else {
                    $res[$v['name']] = [
                        'val' => $v['val']
                    ];
                }
            }

            //\f\pa($res);
        } catch (\PDOException $ex) {

            echo $text = '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
            . PHP_EOL . $ex->getTraceAsString()
            . '</pre>';

            if ( strpos($ex->getMessage(), 'not found') !== false && strpos($ex->getMessage(), 'didrive_vars') !== false ) {

                $ff = $db->prepare('CREATE TABLE `didrive_vars` ( 
                        `folder` VARCHAR(50), 
                        `name` VARCHAR(50) NOT NULL, 
                        `val` VARCHAR(200), 
                        `file` VARCHAR(200) 
                    );');
                $ff->execute();

                if (class_exists('\nyos\Msg'))
                    \nyos\Msg::sendTelegramm('создали таблицу в БД для переменных', null, 1);
            }
        } catch (\Exception $ex) {

            echo $text = '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
            . PHP_EOL . $ex->getTraceAsString()
            . '</pre>';

            if (class_exists('\nyos\Msg'))
                \nyos\Msg::sendTelegramm($text, null, 1);
        } catch (\Throwable $ex) {

            echo $text = '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
            . PHP_EOL . $ex->getTraceAsString()
            . '</pre>';

            if (class_exists('\nyos\Msg'))
                \nyos\Msg::sendTelegramm($text, null, 1);
        }
//        finally {
//            die('неописуемая ситуация #' . __LINE__);
//        }
//        $ff = $db->prepare('DELETE FROM `gm_user_di_mod` WHERE `folder` = :folder AND `user_id` = :user ');
//        $rows = [];
//
//        foreach ($module_list as $k => $v) {
//            $rows[] = array('module' => $k);
//        }
//
//        \f\db\sql_insert_mnogo($db, 'gm_user_di_mod', $rows, array('folder' => $folder, 'user_id' => $user, 'mode' => 'moder'));
        //echo DR . dir_site . 'vars_site.json';
        // file_put_contents(DR . dir_site . self::$cash_file, json_encode($res));
        self::setCash($res);

        return $res;
    }

}
