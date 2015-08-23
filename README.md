## Project Statistics

Сбор и отображение статистики по проекту одним файлом. Необходимо файл pro.php положить в корневой каталог проекта и открыть его через браузер (необходим интерпретатор php5+). Файл осуществляет рекурсивный обход рабочего каталога и собирает статистику, базируясь на расширении файлов. Отображает общую полученную информацию, а также статистику по количеству и размеру файлов различных типов и используемых языках программирования.

**Особенности**

+ Возможность игнорирования файлов определенных типов (.git, .gitignore, .temp и прочее).
+ Объединение однотипных файлов в один набор (изображения, шрифты и прочее).
+ Подсчет количества строк кода в указанных файлах исходников.

**Реализация**

Интерфейс базового класса статистики _ProjectStat_:
+ `__construct($dir)`
+ `Destroy()`
+ `getError()`
+ `getStat()`
+ `getOptions()`
+ `addType($name, $extensions)`
+ `removeType($name)`
+ `addSource($extensions)`
+ `removeSource($extensions)`
+ `addIgnore($extensions)`
+ `removeIgnore($extensions)`
+ `static prepStat($stat)`

Настройки класса по умолчанию:
```php
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
```

Пример наследования класса статистики для *AMP-приложения:
```php
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
```

Пример отображаемой статистики:

![Preview](example.png)
