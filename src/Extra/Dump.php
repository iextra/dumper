<?php

/**
 * This class is for a easy debugging.
 * Examples:
 *  Extra\Dump::toPopup($data) - debug to popup
 *  Extra\Dump::toConsole($data) - debug to console
 *  Extra\Dump::toFile($data) - debug to file
 *
 *
 * This class has an alias for quickly calling the dump function.
 * Examples:
 *  Extra\Dump::p($data) - debug to popup
 *  Extra\Dump::c($data) - debug to console
 *  Extra\Dump::f($data) - debug to file
 */

namespace Extra;

class Dump
{
    /**
     * Default extension for dump files
     *
     * @var string
     */
    private static $defaultExtension = '.txt';

    private static $jsCssInited = false;


    // Functions

    /**
     * @param $data
     * @param string || null $label
     */
    public static function toConsole($data, $label = null)
    {
        // For displaying private and protected properties
        if(is_object($data)){
            $data = (array)$data;
        }

        // If the data has a NAN, then json_encode returns an error
        $data = unserialize(str_replace(['NAN;'],'0;', serialize($data)));

        echo '<script type="text/javascript">
        console.log(' . (empty($label) ? '' : '\'' . $label . '\',') . json_encode($data) . ');
        </script>';
    }

    /**
     * Write to file
     *
     * if $fileName == null that function generate file with name Dump_1__[2020-02-02__16_00_29] etc.
     *
     * @param $data
     * @param sttrin || null $fileName
     * @param string || null $label
     */
    public static function toFile($data, $fileName = null, $label = null){
        $time = date('d.m.Y H:i:s');
        $arTrace = self::getTrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $inFile = self::getCalledFile($arTrace);

        ob_start();
        echo '======================================================================================' . PHP_EOL;
        echo 'Time: ' .  $time . PHP_EOL;
        echo 'File: ' . $inFile;
        if($label !== null){
            echo PHP_EOL . 'Label: ' . $label ;
        }
        echo PHP_EOL . '======================================================================================' . PHP_EOL;
        self::dataPrint($data);
        echo PHP_EOL . PHP_EOL . PHP_EOL;
        $out = ob_get_contents();
        ob_end_clean();

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/' . self::createFileName($fileName), $out, FILE_APPEND);
    }


    /**
     * @param string $data
     * @param string || null $label
     */
    public static function toPopup($data = '', $label = null)
    {
        self::initJsCss();

        $arTrace = self::getTrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        ?>
        <script>
            document.eDump.data.push({
                label: '<?= $label ?>',
                data: <?= JsonData::prepare($data) ?>,
                file: '<?= self::getCalledFile($arTrace) ?>'
            });
        </script>
        <?
    }

    public static function dump($data)
    {
        echo '<pre>';
        self::dataPrint($data);
        echo '</pre>';
    }

    /**
     * Get call stack
     *
     * @param int $options (DEBUG_BACKTRACE_IGNORE_ARGS - exclude the "args" key to reduce memory consumption)
     * @param int $limit
     * @return array
     */
    public static function getTrace($options = 0, $limit = 0)
    {
        ob_start();
        debug_print_backtrace($options, $limit);
        $trace = ob_get_contents();
        ob_end_clean();

        $arTrace = explode("\n", $trace);
        array_pop($arTrace); // Remove last empty from stack

        return $arTrace;
    }

    // Function aliases for fast call

    /**
     * Short call function toPopup()
     *
     * @param string $data
     * @param string || null $label
     */
    public static function p($data = '', $label = null)
    {
        self::toPopup($data, $label);
    }

    /**
     * Short call function toFile()
     *
     * @param $data
     * @param string || null $fileName
     * @param string || null $label
     */
    public static function f($data, $fileName = null, $label = null)
    {
        self::toFile($data, $fileName, $label);
    }

    /**
     * Short call function dump()
     *
     * @param $data
     */
    public static function d($data)
    {
        self::dump($data);
    }

    /**
     * Short call function toConsole()
     *
     * @param $data
     * @param string $label
     */
    public static function c($data, $label = '')
    {
        self::toConsole($data, $label);
    }

    /**
     * Dump and die
     *
     * @param $data
     */
    public static function dd($data)
    {
        self::dump($data);
        die();
    }


    //html

    /**
     * Render Js and Css
     */
    private static function initJsCss()
    {
        if(self::$jsCssInited === false){
            self::initJs();
            self::initCss();

            self::$jsCssInited = true;
        }
    }

    /**
     * Render JS
     */
    private static function initJs()
    {
        ?>
        <script>
            ;(function ExtraDump() {

                var privProp = '__privProp';
                var protProp = '__protProt';
                var clNameProp = '__className';

                var clBool = 'dBool';
                var clStr = 'dStr';
                var clInt = 'dInt';
                var clCount = 'dCnt';
                var clPlus = 'dPl';
                var clName = 'dName';
                var clProp = 'dProp';
                var clSystem = 'dSys';
                var clClose = 'dClose';
                var clKey = 'dKey';

                var clDump = 'dBox';
                var clParent = 'dPrnt';
                var clChild = 'dCld';

                function wrapKey(key, parentType) {
                    var node;
                    var regex = new RegExp('^[0-9]+$');

                    if (parentType === 'object')
                        node = wrapProp(key);
                    else if (regex.test(key) === true)
                        node = wrapInt(key);
                    else
                        node = createNode('span', clKey, "'" + key + "'");

                    return node;
                }

                function wrapKeyValue(key, data, parentType) {
                    var node = createNode('div', 'dLine');
                    node.appendChild(wrapKey(key, parentType));

                    if (parentType === 'array')
                        node.appendChild(createNode('span', clSystem, ' => '));
                    else
                        node.appendChild(createNode('span', clSystem, ': '));

                    node.appendChild(wrap(data));

                    return node;
                }

                function createChild(data, parentType) {
                    var node = createNode('div', clChild);

                    for (var key in data)
                        node.appendChild(wrapKeyValue(key, data[key], parentType));

                    return node;
                }

                function wrapArrCount(length) {
                    return createNode('small', clCount, '(' + length + ')');
                }

                function wrapArr(data) {
                    var dataLength = 0;
                    if (data !== null)
                        dataLength = Object.keys(data).length;

                    var node = createNode('span', clParent);
                    node.appendChild(createNode('span', clName, 'array:'));

                    if (dataLength > 0)
                        node.appendChild(wrapArrCount(dataLength));

                    node.appendChild(createNode('span', clName, '['));

                    if (dataLength > 0) {
                        node.appendChild(createNode('span', clPlus));
                        node.appendChild(createChild(data, 'array'));
                    }

                    node.appendChild(createNode('span', clName, ']'));

                    return node;
                }

                function wrapObj(data) {
                    var node = createNode('span', clParent);

                    var className = getClassName(data);
                    var arName = className.split('\\');

                    var classNode = createNode('span', clName, arName.pop());
                    if(arName.length > 0)
                        classNode.title = className;

                    node.appendChild(classNode);
                    node.appendChild(createNode('span', clName, '{'));

                    if (data !== null && Object.keys(data).length > 0) {
                        if (data.hasOwnProperty(clNameProp))
                            delete data[clNameProp];

                        node.appendChild(createNode('span', clPlus));
                        node.appendChild(createChild(data, 'object'));
                    }

                    node.appendChild(createNode('span', clName, '}'));

                    return node;
                }

                function wrapProp(data) {
                    var regPriv = new RegExp('^' + privProp + '*');
                    var regProt = new RegExp('^' + protProp + '*');
                    var node = createNode('span');

                    if(regPriv.test(data)) {
                        node.appendChild(createNode('span', clProp, '*'));
                        node.appendChild(document.createTextNode(data.substring(10)));
                    }
                    else if (regProt.test(data)) {
                        node.appendChild(createNode('span', clProp, '#'));
                        node.appendChild(document.createTextNode(data.substring(10)));
                    }
                    else
                        node.appendChild(document.createTextNode(data));

                    return  node;
                }

                function wrapStr(data) {
                    return createNode('span', clStr, "'" + data + "'");
                }

                function wrapInt(data) {
                    return createNode('span', clInt, data);
                }

                function wrapBool(data) {
                    return createNode('span', clBool, data);
                }

                function wrap(data) {
                    var node;
                    var type = getType(data);

                    if (type === 'string') {
                        node = wrapStr(data);
                    }
                    else if (type === 'number'){
                        node = wrapInt(data);
                    }
                    else if (data === null){
                        node = wrapBool('null');
                    }
                    else if (type === 'object'){
                        node = wrapObj(data);
                    }
                    else if (type === 'array'){
                        node = wrapArr(data);
                    }
                    else if (type === 'boolean'){
                        node = wrapBool(data);
                    }

                    return node;
                }

                function dump(data) {
                    var container = createNode('div', clDump + ' exOpen');
                    container.id = 'dData_' + data.index;

                    container.appendChild(createNode('span', clClose, '✕'));

                    var infoBox = createNode('span', clProp, (++data.index) + '. ');
                    infoBox.setAttribute('info', 'File: ' + data.file);
                    container.appendChild(infoBox);

                    container.appendChild(wrap(data.data));

                    var mainContainer = document.getElementById('dContainer');
                    mainContainer.appendChild(container);
                }

                function createNode(nodeType, className = undefined, data = '') {
                    var node = document.createElement(nodeType);

                    if (className !== undefined && className.length > 0)
                        node.className = className;

                    node.innerText = data;

                    return node;
                }

                function getType(data) {
                    var type = typeof data;

                    if(type === 'object' && getClassName(data) === 'object'){
                        return 'array';
                    }

                    return type;
                }

                function getClassName(object) {
                    for (var key in object)
                        if (key === clNameProp)
                            return object[key];
                    return 'object';
                }

                //
                function trigger(element, parent) {
                    if (element.style.display === 'block') {
                        element.style.display = 'none';
                        parent.classList.remove('exOpen');
                    }
                    else {
                        element.style.display = 'block';
                        parent.classList.add('exOpen');
                    }
                }

                function createPopup(item, i) {
                    var li = createNode('li');
                    var container = createNode('div', 'dMenu');


                    var number = createNode('span', undefined, (i + 1) + '.');
                    container.appendChild(number);

                    container.appendChild(document.createTextNode(' '));

                    var label = createNode('i', undefined, item.label);
                    container.appendChild(label);

                    var btnOpen = createNode('div', 'dMenuBtn exOpen', '🔎'); // &#128270;
                    btnOpen.setAttribute('data-index', i);
                    container.appendChild(btnOpen);

                    var btnClose = createNode('div', 'dMenuBtn exClose', '✕');
                    container.appendChild(btnClose);

                    li.appendChild(container);
                    return li;
                }

                function dumpInit() {
                    // Create container
                    var node = document.createElement('div');
                    node.id = 'dContainer';
                    document.body.insertBefore(node, document.body.firstChild);

                    // Crete control popups
                    if(document.eDump.data.length > 0){
                        var ul = createNode('ul', 'dump-popup-list');

                        document.eDump.data.forEach(function (item, i) {
                            ul.appendChild(createPopup(item, i));
                        });

                        //document.body.appendChild(ul);
                        var container = document.getElementById('dContainer');
                        container.appendChild(ul);
                    }

                    // Events
                    var openButtons = document.querySelectorAll('.dMenuBtn.exOpen');
                    for (i = 0; i < openButtons.length; ++i) {
                        openButtons[i].onclick = function () {
                            var index = this.getAttribute('data-index');
                            var id = 'dData_' + index;
                            var element = document.getElementById(id);

                            if(element !== null){
                                element.classList.add('exOpen');
                            }
                            else if(id !== undefined && id.length > 0) {
                                dump({
                                    index: index,
                                    data: document.eDump.data[index].data,
                                    label: document.eDump.data[index].label,
                                    file: document.eDump.data[index].file
                                });
                                addEventClickForDump();
                            }
                        }
                    }

                    var closeButtons = document.querySelectorAll('.dMenuBtn.exClose');
                    for (i = 0; i < closeButtons.length; ++i) {
                        closeButtons[i].onclick = function () {
                            this.parentNode.remove();
                        }
                    }
                }

                function addEventClickForDump() {
                    var elements = document.querySelectorAll('.' + clPlus);
                    for (i = 0; i < elements.length; ++i) {
                        elements[i].onclick = function () {
                            trigger(this.nextElementSibling, this);
                        }
                    }
                    var items = document.querySelectorAll('.dClose');
                    for (i = 0; i < items.length; ++i) {
                        items[i].onclick = function () {
                            this.parentElement.classList.remove('exOpen');
                        }
                    }
                }

                document.ExtraDump = ExtraDump;
                document.eDump = {data: []};
                document.addEventListener("DOMContentLoaded", dumpInit);
            }());
        </script>
        <?
    }

    /**
     * Render Css
     */
    private static function initCss()
    {
        ?>
        <style>
            #dContainer{position: fixed; z-index: 999999; padding: 0; top: 0; left: 0; margin: 8px;}
            .dBox{
                font: 12px/1.17em Consolas, Verdana, sans-serif;
                background-color: #272822;
                color: #fdfdfd;
                padding: 5px;
            }
            .dName{color: #3e92db;}
            .dPl{color: #4caf50;}
            .dInt{color: #ae81ff;}
            .dStr{color: #ffc107;}
            .dBool{color: #f8263e;}
            .dSys{color: #f8263e;}
            .dProp{color: #f8263e; padding-right: 2px;}
            .dCnt{color: #a0a0a0;}
            .dPl::after{color: #5be767;cursor: pointer;content: '+';}
            .dPl.exOpen::after{content: '-';}
            .dPrnt .dCld{display: none; margin-left: 25px;}
            .dKey{color: #e5da67;}

            .dBox{display: none;}
            .dBox.exOpen{
                display: block;
                width: 600px;
                background-color: #272822;
                border: 1px solid #000;
                position: relative;
                overflow: auto;
                resize: both;
                z-index: 999999;
                max-height: 99.5vh;
                word-break: break-all;
                overflow-x: hidden;
                margin-bottom: 1px;
                min-height: 14px;
            }
            .dClose{
                cursor: pointer;
                color: #fdfdfd;
                position: sticky;
                float: right;
                top: 0;
                right: 0;
                padding: 0 5px;
                /*margin: 5px;*/
            }
            .dump-popup-list{
                position: fixed;
                max-height: 98vh;
                padding: 0;
                margin: 0;
                z-index: 999998;
                overflow-y: auto;
                padding-right: 15px;
                min-height: 26px;
            }
            .dump-popup-list li{
                list-style: none;
                margin-bottom: 1px;
            }
            *[info]{
                cursor: help;
                white-space:pre-wrap;
            }
            *[info]:hover:after {
                content: attr(info);
                border: 1px bisque solid;
                background-color: blanchedalmond;
                padding: 2.5px 5px;
                position: fixed;
                color: #222;
                white-space:pre-wrap;
            }
            .dMenu{
                /*position: fixed;*/
                z-index: 999998;
                border: 1px solid #000000;
                background-color: #272822;
                color: #f8263e;
                text-align: left;
                left: 8px;
                font: 12px/1.17em Consolas, Verdana, sans-serif;
                line-height: 22px;
                padding: 1px;
            }
            .dMenu span {margin-left: 5px;}
            .dMenuBtn.exOpen{padding-top: 1px;}
            .dMenuBtn.delete{padding-top: 1px;}
            .dMenu i {padding-right: 15px;}
            @-moz-document url-prefix(){ .dMenu span {margin-left: 5px;} }
            .dMenuBtn {
                font: 11px/1.27em Gotham, Verdana, Tahoma;
                color: white;
                padding: 0 5px;
                cursor: pointer;
                float: right;
                line-height: 22px;
            }
        </style>
        <?
    }

    //helpers

    private static function dataPrint($data){
        if(is_array($data) || is_object($data)){
            print_r($data);
        }
        else if(is_bool($data)){
            echo ($data === true) ? 'true' : 'false';
        }
        else if(is_null($data)){
            echo 'null';
        }
        else{
            echo $data;
        }
    }

    /**
     * Create file name for dump files
     *
     * @param null $fileName
     * @return string
     */
    private static function createFileName($fileName = null)
    {
        static $i = 0;

        if(!empty($fileName) && strlen(trim($fileName)) > 0){
            if(strpos($fileName, '.txt') !== false || strpos($fileName, '.log') !== false){
                $result =  $fileName;
            }
            else{
                $result =  $fileName . self::$defaultExtension;
            }
        }
        else{
            $result = 'Dump_' . ++$i . '__[' . date('Y-m-d__H_i_s') . ']' . self::$defaultExtension;
        }

        return $result;
    }

    /**
     * Returns the file and the line where the debug function was called from
     *
     * @param $arTrace
     * $return string
     */
    private static function getCalledFile($arTrace)
    {
        $arr = [];
        foreach($arTrace as $trace){
            if(strpos($trace, __CLASS__) !== false){
                $arr[] = $trace;
            }
            else{
                break;
            }
        }

        $path = preg_split('/called at /', end($arr))[1] ?: '';
        return substr($path, 1, -1);
    }

}

/**
 * This class preparing data for JS
 *
 * Class JsonData
 * @package Extra
 */
class JsonData
{
    private static
        $propClassName = '__className',
        $protectedLabel = '__privProp',
        $privateLabel = '__protProp';

    public static function prepare($data){
        return json_encode( self::prepareData($data) );
    }

    private static function prepareData($data)
    {
        //var_dump($data);die();
        $type = self::getType($data);
        $result = self::createType($type);

        if($type === 'object' || $type === 'array')
        {
            $className = null;
            if($type === 'object'){
                $className = get_class($data);
                $propClassName = self::$propClassName;
                $result->$propClassName = $className;
            }

            array_walk($data, function($value, $key) use ($type, $className, &$result){

                if($type === 'object'){
                    $key = self::addVisibilityWrapper($key, $className);
                }

                $valueType = self::getType($value);
                if($valueType === 'object' || $valueType === 'array'){
                    $value = self::prepareData($value);
                }
                else if($valueType === 'double'){
                    $value = is_nan($value) ? 'NaN' : $value;
                }


                if($type === 'object'){
                    $key = self::prepareClassKey($key);
                    $result->$key = $value;
                }
                else if($type === 'array'){
                    $result[$key] = $value;
                }
            });
        }
        else if($type === 'double'){
            $result = is_nan($data) ? 'NaN' : $data;
        }
        else{
            $result = $data;
        }

        unset($data);
        return $result;
    }

    private static function createType($type)
    {
        if($type === 'array'){
            return [];
        }
        else if($type === 'object'){
            return new \stdClass();
        }
        else{
            return null;
        }
    }

    private static function getType($data)
    {
        return gettype($data);
    }

    private static function addVisibilityWrapper($prop, $className)
    {
        if(strpos($prop, $className) !== false){
            $prop = str_replace($className, self::$privateLabel, $prop);
            $prop = str_replace("\0", '', $prop);
        }
        else if($prop[1] === '*'){
            $prop = str_replace('*', self::$protectedLabel, $prop);
            $prop = str_replace("\0", '', $prop);
        }
        return $prop;
    }

    private static function prepareClassKey($key)
    {
        return str_replace("\0", ' ', $key);
    }
}
