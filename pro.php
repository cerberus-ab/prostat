<?php

    /**
     * Project Statistics
     * @author cerberus.ab <cerberus.ab@mail.ru>
     * @version 27.05.15
     * @since 25.05.15
     *
     */

    // Набор классов и функций =================================================

    /**
     * Класс формирования статистики по проекту
     */
    class ProjectStat {

        /** @type [string] текст последней ошибки */
        private $error = null;
        /** @type [string] корневая директория проекта */
        private $dir = null;

        /** @type [array] настройки */
        private $options = array(
            /** @type [array] список расширений игнорируемых файлов */
            'ignore' => array('gitignore', 'git'),
            /** @type [array] список расширений исходников */
            'source' => array(),
            /** @type [array] возможные типы файлов */
            'types' => array(
                'image' => array('jpg', 'png', 'bmp', 'gif'),
                'font' => array('eot', 'ttf', 'woff')
            )
        );

        /** @type [array] статистика по умолчанию */
        private $stat_default = array(
            /** @type [array] общая информация */
            'main' => array(
                /** @type [integer] количество файлов и каталогов */
                'files' => 0,
                'folders' => 0,
                /** @type [integer] количество строк кода в исходниках */
                'source' => 0,
                /** @type [integer] суммарный размер файлов в байтах */
                'size' => 0,
                /** @type [string] директория и название каталога */
                'dirname' => null,
                'basename' => null
            ),
            /** @type [array] информация о производительности */
            'performance' => array(
                /** @type [integer] время выполнения в мкс */
                'elapsed_time' => 0
            ),
            /** @type [array] файлы */
            'files' => array(),
            /** @type [array] исходники */
            'source' => array()
        );

        // Конструкторы и деструкторы ==========================================

        /**
         * Конструктор
         * @param [string] $dir корневая директория проекта
         */
        public function __construct($dir) {
            $this->dir = $dir;
            if (!is_dir($this->dir)) {
                $this->error = 'Target "'.$this->dir.'" is not a directory!';
            }
            $realpath = realpath($dir);
            $this->stat_default['main']['dirname'] = dirname($realpath);
            $this->stat_default['main']['basename'] = basename($realpath);
        }

        /**
         * Деструктор
         */
        public function Destroy() {
            // do nothing
        }

        /**
         * Получить текст последней ошибки
         * @return [string] текст последней ошибки
         */
        public function getError() {
            return $this->error;
        }

        // Приватные методы ====================================================

        /**
         * Преобразовать в корректный для работы массив (расширений)
         * @param  [mix] $target целевые данные
         * @return [array] как корректный массив
         */
        private function toReadyArray($target) {
            return is_array($target) ? $target : array($target);
        }

        /**
         * Добавить расширения в список
         * @param  [array] &$list ссылка на целевой список
         * @param  [array|string] $extensions расширения
         * @return [integer] количество добавленных расширений
         */
        private function addExtension(&$list, $extensions) {
            $length_before = count($list);
            $list = array_merge($list, $this->toReadyArray($extensions));
            return count($list) - $length_before;
        }

        /**
         * Удалить расширения из списка
         * @param  [array] &$list ссылка на целевой список
         * @param  [array|string|null] $extensions расширения или null для очистки
         * @return [integer] количество удаленных расширений
         */
        private function removeExtension(&$list, $extensions = null) {
            $length_before = count($list);
            $list = is_null($extensions) ? array() : array_diff($list, $this->toReadyArray($extensions));
            return $length_before - count($list);
        }

        /**
         * Определение типа файла
         * @param  [string] $extension расширение файла
         * @return [string] тип файла
         */
        private function getFileType($extension) {
            foreach ($this->options['types'] as $type => $list) {
                if (in_array($extension, $list)) return $type;
            }
            return $extension;
        }

        /**
         * Сохранить файл в статистику
         * @param  [array] $file_obj информация (type, size)
         * @param  [array] &$files ссылка на массив файлов по типам
         */
        private function rmbFile($file_obj, &$files) {
            foreach ($files as &$current) {
                if (strcmp($current['type'], $file_obj['type']) === 0) {
                    $current['amount']++;
                    $current['size'] += $file_obj['size'];
                    return;
                }
            }
            array_push($files, array(
                'type' => $file_obj['type'],
                'size' => $file_obj['size'],
                'amount' => 1
            ));
        }

        /**
         * Сохранить инф. об исходнике в статистику
         * @param  [array] $file_obj информация (type, count)
         * @param  [array] &$source ссылка на массив исходников
         */
        private function rmbCode($file_obj, &$source) {
            foreach ($source as &$current) {
                if (strcmp($current['type'], $file_obj['type']) === 0) {
                    $current['count'] += $file_obj['count'];
                    return;
                }
            }
            array_push($source, array(
                'type' => $file_obj['type'],
                'count' => $file_obj['count']
            ));
        }

        /**
         * Обработка файла
         * @param  [string] $file файл
         * @param  [array] &$stat ссылка на статистику
         */
        private function passFile($file, &$stat) {
            // информация о файле
            $file_info = pathinfo($file);
            // если файл не игнорируется, то продолжить
            if (!in_array($file_info['extension'], $this->options['ignore'])) {
                // получение типа файла
                $type = $this->getFileType($file_info['extension']);
                // размер файла в байтах
                $size = filesize($file);
                $stat['main']['size'] += $size;
                // сохранить файл
                $this->rmbFile(array(
                    'type' => $type,
                    'size' => $size
                ), $stat['files']);
                // если файл является исходником
                if (in_array($file_info['extension'], $this->options['source'])) {
                    $count = count(file($file));
                    $stat['main']['source'] += $count;
                    // сохранить исходник
                    $this->rmbCode(array(
                        'type' => $type,
                        'count' => $count
                    ), $stat['source']);
                }
            }
        }

        /**
         * Обработка директории
         * @param  [string] $dir директория
         * @param  [array] &$stat ссылка на статистику
         */
        private function passDir($dir, &$stat) {
            $items = array_diff(scandir($dir), array('.', '..'));
            foreach ($items as $item) {
                $path = $dir.'/'.$item;
                // если является директорией
                if (is_dir($path)) {
                    $stat['main']['folders']++;
                    $this->passDir($path, $stat);
                }
                // если является файлом
                else if (is_file($path)) {
                    $stat['main']['files']++;
                    $this->passFile($path, $stat);
                }
            }
        }

        // Публичные методы ====================================================

        /**
         * Обработка файла статистики
         * @param  [array] $stat статистика
         * @return [array] готовая статистика
         */
        public static function prepStat($stat) {
            // сортировка списка файлов
            usort($stat['files'], function($a, $b) {
                return strcmp($a['type'], $b['type']);
            });
            usort($stat['source'], function($a, $b) {
                return strcmp($a['type'], $b['type']);
            });
            // получить максимальное количество файлов одного типа
            $files_amount_max = array_reduce($stat['files'], function($carry, $item) {
                return $item['amount'] > $carry ? $item['amount'] : $carry;
            });
            // получить максимальный размер файлов одного типа
            $files_size_max = array_reduce($stat['files'], function($carry, $item) {
                return $item['size'] > $carry ? $item['size'] : $carry;
            });
            // получить максимальное количество строк кода файлов одного типа
            $source_count_max = array_reduce($stat['source'], function($carry, $item) {
                return $item['count'] > $carry ? $item['count'] : $carry;
            });
            // подсчет относительного количества и размера файлов
            foreach ($stat['files'] as &$item) {
                $item['amount_rel_sum'] = round($item['amount'] / $stat['main']['files'], 4);
                $item['amount_rel_max'] = round($item['amount'] / $files_amount_max, 4);
                $item['size_rel_sum'] = round($item['size'] / $stat['main']['size'], 4);
                $item['size_rel_max'] = round($item['size'] / $files_size_max, 4);
            }
            // подсчет относительного количества строк кода
            foreach ($stat['source'] as &$item) {
                $item['count_rel_sum'] = round($item['count'] / $stat['main']['source'], 4);
                $item['count_rel_max'] = round($item['count'] / $source_count_max, 4);
            }
            // вернуть
            return $stat;
        }

        /**
         * Получить статистику по проекту
         * @return [array] статистика
         */
        public function getStat() {
            $tbeg = microtime(true);
            $stat = $this->stat_default;
            $this->passDir($this->dir, $stat);
            $tend = microtime(true);
            $stat['performance']['elapsed_time'] = round(($tend - $tbeg) * 1000000);
            return $stat;
        }

        /**
         * Получить текущие настройки
         * @return [array] настройки
         */
        public function getOptions() {
            return $this->options;
        }

        /**
         * Добавить новый тип
         * @param [string] $name название типа
         * @param [array] $extensions массив расширений
         */
        public function addType($name, $extensions) {
            $this->options['types'][$name] = $extensions;
        }

        /**
         * Удалить тип
         * @param  [string] $name название типа
         */
        public function removeType($name) {
            if (isset($this->options['types'][$name])) {
                unset($this->options['types'][$name]);
            }
        }

        /**
         * Добавить новое расширение в список исходников
         * @param  [array|string] $extensions расширения
         * @return [integer] количество добавленных расширений
         */
        public function addSource($extensions) {
            return $this->addExtension($this->options['source'], $extensions);
        }

        /**
         * Удалить расширения из списка исходников
         * @param  [array|string|null] $extensions расширения или null для очистки
         * @return [integer] количество удаленных расширений
         */
        public function removeSource($extensions = null) {
            return $this->removeExtension($this->options['source'], $extensions);
        }

        /**
         * Добавить новое расширение в список игнорирования
         * @param  [array|string] $extensions расширения
         * @return [integer] количество добавленных расширений
         */
        public function addIgnore($extensions) {
            return $this->addExtension($this->options['ignore'], $extensions);
        }

        /**
         * Удалить расширения из списка игнорирования
         * @param  [array|string|null] $extensions расширения или null для очистки
         * @return [integer] количество удаленных расширений
         */
        public function removeIgnore($extensions = null) {
            return $this->removeExtension($this->options['ignore'], $extensions);
        }
    }

    /**
     * Класс формирования статистики *AMP-приложения
     */
    class ProjectStat_AMP extends ProjectStat {

        /**
         * Конструктор
         * @param [string] $dir корневая директория проекта
         */
        public function __construct($dir) {
            // создание экземпляра
            parent::__construct($dir);

            // расширения файлов игнорирования
            $this->addIgnore(array('htaccess'));
            // расширения файлов исходников
            $this->addSource(array('js', 'php', 'css', 'cpp', 'h'));
            // определение типов файлов
            $this->addType('cpp', array('cpp', 'h'));
        }
    }

    /**
     * Функция нормального представления размера файла
     * @param  [integer] $size размер файла в байтах
     * @param  [integer] $round округление
     * @return [string] размер для представления
     */
    function size_normal_view($size, $round = 2) {
        $pow = array('', 'k','M','G', 'T', 'P', 'E', 'Z', 'Y');
        for ($i = 0; $size > 1024; $size /= 1024, $i++);
        return round($size, $round).$pow[$i].'B';
    }

    // Выполнить инициализацию =================================================

    // инициализация объекта статистики
    $pro = new ProjectStat_AMP('./');
    if ($error = $pro->getError()) {
        die($error);
    }

    // получить текущие настройки
    $options = $pro->getOptions();

    // получить статистику в текущем каталоге
    $stat = ProjectStat_AMP::prepStat($pro->getStat());

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Project Statistics: <?=$stat['main']['basename']?></title>
    <!-- Stylesheet -->
    <style type="text/css" name="default">
        html, body, div, span, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, em, font, img, ins, small, strong, sub, sup, b, u, i, center, ol, ul, li, fieldset, form, label, legend, table, tbody, th, td {
            border: 0;
            outline: 0;
            font-size: 100%;
            margin: 0;
            padding: 0;
        }
        html {
            height: 100%;
            width: 100%;
        }
        div, input, select, textarea {
            margin: 0;
            padding: 0;
            box-sizing: border-box !important;
             -moz-box-sizing: border-box !important;
             -webkit-box-sizing: border-box !important;
        }
        table {
            width: 100%;
            border-spacing: 0;
        }
        ul {
            list-style-type: none;
        }
        body {
            height: 100%;
            width: 100%;
            color: #3F3F3F;
            font: 14px Arial, Helvetica, sans-serif;
        }
    </style>
    <style type="text/css" name="stat">
        body {
            background: rgba(220,220,220,1);
        }
        .stat {
            border-spacing: 10px;
            table-layout: fixed;
        }
        .stat_area {
            vertical-align: top;
            border: 1px solid rgba(63,63,63,1);
            padding: 4px 10px 16px 10px;
            background: white;
        }
        tr.stat_table_head > td {
            font-size: 16px;
            padding-bottom: 20px;
        }
        .stat_table {
            border-spacing: 0px 1px;
        }
        .stat_table tr:not(.stat_table_head) {
            height: 24px;
        }

        .stat_main tr.stat_table_head > td {
            font-size: 18px;
        }
        .stat_main tr:not(.stat_table_head) > td:nth-child(1) {
            width: 200px;
        }
        .stat_main tr:not(.stat_table_head) > td:nth-child(2) {
            font-family: monospace;
        }

        .stat_graph tr:not(.stat_table_head) > td {
            font-family: monospace;
        }
        .stat_graph tr:not(.stat_table_head) > td:nth-child(1) {
            width: 80px;
            padding-right: 8px;
            text-align: right;
            background: rgba(192,192,192,1);
            background: linear-gradient(to left, rgba(192,192,192,1), rgba(255,255,255,1));
        }
        .stat_graph tr:not(.stat_table_head) > td:nth-child(2) {
            padding-left: 2px;
            cursor: default;
            padding-right: 64px;
        }

        .stat_entry {
            position: relative;
            min-width: 2px;
            height: 24px;
            background: rgba(178,178,178,1);
        }
        .stat_entry p {
            line-height: 24px;
            width: 60px;
            position: absolute;
            top: 0;
            right: -64px;
            cursor: help;
        }
        #stat_files_amount .stat_entry {
            background: rgba(34,178,34,1);
        }
        #stat_files_size .stat_entry {
            background: rgba(34,34,178,1);
        }
        #stat_source_count .stat_entry {
            background: rgba(178,34,34,1);
        }

        .file_label {
            display: inline-block;
        }
        .file_label.multiple {
            border-bottom: 1px dotted #3F3F3F;
            cursor: help;
        }
    </style>
</head>
<body>
    <table class="stat">
        <tr class="stat_head"><td colspan="3" class="stat_area">
            <table class="stat_table stat_main">
                <tr class="stat_table_head"><td colspan="2">Общая информация</td></tr>
                <tr><td>Название:</td><td><?=$stat['main']['basename']?></td></tr>
                <tr><td>Расположение:</td><td><?=$stat['main']['dirname']?></td></tr>
                <tr><td>Каталогов:</td><td><?=$stat['main']['folders']?></td></tr>
                <tr><td>Файлов:</td><td><?=$stat['main']['files']?></td></tr>
                <tr><td>Размер:</td><td><?=size_normal_view($stat['main']['size'])?> (<?=$stat['main']['size']?> bytes)</td></tr>
                <tr><td>Исходники:</td><td><?=$stat['main']['source']?> строк</td></tr>
            </table>
        </td></tr>
        <tr class="stat_body">
            <td name="amount" class="stat_area">
                <table class="stat_table stat_graph" id="stat_files_amount">
                    <tr class="stat_table_head">
                        <td colspan="2">Статистика по количеству файлов</td>
                    </tr>
                    <?php foreach ($stat['files'] as $item): ?>
                        <tr>
                        <td>
                            <?php if (isset($options['types'][$item['type']])): ?>
                                <p title="<?=(implode(', ', $options['types'][$item['type']]))?>" class="file_label multiple"><?=$item['type']?></p>
                            <? else: ?>
                                <p class="file_label"><?=$item['type']?></p>
                            <? endif; ?>
                        </td>
                        <td>
                            <div class="stat_entry" style="width:<?=($item['amount_rel_max'] *100)?>%">
                                <p title="файлов: <?=$item['amount']?>"><?=($item['amount_rel_sum'] *100)?>%</p>
                            </div>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
            <td name="size" class="stat_area">
                <table class="stat_table stat_graph" id="stat_files_size">
                    <tr class="stat_table_head">
                        <td colspan="2">Статистика по размеру файлов</td>
                    </tr>
                    <?php foreach ($stat['files'] as $item): ?>
                        <tr>
                        <td>
                            <?php if (isset($options['types'][$item['type']])): ?>
                                <p title="<?=(implode(', ', $options['types'][$item['type']]))?>" class="file_label multiple"><?=$item['type']?></p>
                            <? else: ?>
                                <p class="file_label"><?=$item['type']?></p>
                            <? endif; ?>
                        </td>
                        <td>
                            <div class="stat_entry" style="width:<?=($item['size_rel_max'] *100)?>%">
                                <p title="размер: <?=size_normal_view($item['size'])?> (<?=$item['size']?> bytes)"><?=($item['size_rel_sum'] *100)?>%</p>
                            </div>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
            <td name="count" class="stat_area">
                <table class="stat_table stat_graph" id="stat_source_count">
                    <tr class="stat_table_head">
                        <td colspan="2">Используемые языки</td>
                    </tr>
                    <?php foreach ($stat['source'] as $item): ?>
                        <tr>
                        <td>
                            <?php if (isset($options['types'][$item['type']])): ?>
                                <p title="<?=(implode(', ', $options['types'][$item['type']]))?>" class="file_label multiple"><?=$item['type']?></p>
                            <? else: ?>
                                <p class="file_label"><?=$item['type']?></p>
                            <? endif; ?>
                        </td>
                        <td>
                            <div class="stat_entry" style="width:<?=($item['count_rel_max'] *100)?>%">
                                <p title="строк: <?=$item['count']?>"><?=($item['count_rel_sum'] *100)?>%</p>
                            </div>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
    </table>
    <!-- Javascript Code -->
    <script type="text/javascript">
        var app = {
            options: <?php echo json_encode($options); ?>,
            stat: <?php echo json_encode($stat); ?>
        };
    </script>
</body>
</html>
