<?php

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

AddEventHandler("iblock", "OnIBlockPropertyBuildList", ["CustomProperty", "GetUserTypeDescription"]);

class CustomProperty
{
    private static array $arInputsValue = [];

    /**
     * @return array 'Массив, описывающий кастомное свойство'
     */
    public static function GetUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID' => 'custom_property',
            'USER_TYPE' => 'CUSTOM_PROPERTY',
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => 'Кастомное свойство файл+строка+HTML/text',
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'], // Открытие карточки элемента инфоблока
            "GetSettingsHTML" => [__CLASS__, "GetSettingsHTML"], // Открытие настроек свойства в настройках инфоблока
//            "PrepareSettings" => [__CLASS__, "PrepareSettings"], // Сохранение свойства в настройках инфоблока
            'ConvertToDB' => [__CLASS__, "ConvertToDB"], // Сохранении свойства в карточке элемента (b_iblock_element_property)
            'ConvertFromDB' => [__CLASS__, "ConvertFromDB"], // Извлечение свойства в карточку элемента (b_iblock_element_property)
        ];
    }

    public static function GetPropertyFieldHtml($arProperty, $arValue, $arHtmlControl): string
    {
        Loader::includeModule('fileman');

        self::setInputsValue($arValue);
        return self::getInputsHtml($arHtmlControl);
    }

    private static function setInputsValue($arValue): void
    {
        self::$arInputsValue = [
            'STRING' => '',
            'FILE_ID' => '',
            'TEXTAREA' => []
        ];

        if (!is_array($arValue['VALUE'])) {
            return;
        }

        if (!empty($arValue['VALUE']['STRING'])) {
            self::$arInputsValue['STRING'] = $arValue['VALUE']['STRING'];
        }

        if (!empty($arValue['VALUE']['FILE_ID'])) {
            self::$arInputsValue['FILE_ID'] = $arValue['VALUE']['FILE_ID'];
        }

        if (!empty($arValue['VALUE']['TEXTAREA'])) {
            self::$arInputsValue['TEXTAREA'] = $arValue['VALUE']['TEXTAREA'];
        }
    }

    private static function getInputsHtml($arHtmlControl): string
    {
        $inputBaseName = htmlspecialcharsbx($arHtmlControl['VALUE']);

        $html = '<label for="custom-string">Строка:</label><br>
                <input
                style="width: 300px;"
                id="custom-string"
                name="' . $inputBaseName . '[STRING]"
                type="text"
                value="' . self::$arInputsValue['STRING'] . '">';

        $html .= '<input
                name="' . $inputBaseName . '[SAVED_FILE_ID]"
                type="hidden"
                value="' . self::$arInputsValue['FILE_ID'] . '">';

        $html .= '<br><p class="custom-file">Файл:</p>';

        $fileInput = CFile::InputFile(
            $inputBaseName . '[FILE]',
            20,
            self::$arInputsValue['FILE_ID'],
            strFileType: '',
            field_checkbox: 'data-original-name="' . $arHtmlControl['VALUE'] . '[DELETE_FILE]"',
            bShowFilePath: false,
        );

        $html .= $fileInput . '<br><br>';
        $html .= '<label for="custom-textarea">HTML/TEXT:</label>';
        $html .= self::getEditorHtml($arHtmlControl);
        $html .= '<br><br><br>';

        return $html;
    }

    private static function getEditorHtml($arHtmlControl): string
    {
        ob_start();
        CFileMan::AddHTMLEditorFrame(
            $arHtmlControl['VALUE'] . '[TEXTAREA][TEXT]',
            self::$arInputsValue['TEXTAREA']['TEXT'] ?? '',
            $arHtmlControl['VALUE'] . '[TEXTAREA][TYPE]',
            self::$arInputsValue['TEXTAREA']['TYPE'] ?? '',
            array(
                'height' => 100,
                'width' => '100%'
            ),
            textarea_field: 'data-original-name="' . $arHtmlControl['VALUE'] . '[TEXTAREA][TEXT]"',
        );

        ?>
        <script>
            window.addEventListener('load', function() {

                // Исправляем name для textarea и input, удаляющий файл, т.к. их name изменяется в
                // /bitrix/modules/fileman/fileman.php и /bitrix/modules/main/classes/general/file.php
                let elements = document.querySelectorAll('[data-original-name]')
                for (let element of elements) {
                    element.name = element.dataset.originalName;
                }
            });
        </script>

        <?php
        return ob_get_clean();
    }

    public static function ConvertToDB($arProperty, $arValue)
    {
        // НЕ сохранять пустые свойства (важно, если поле множественное)
        if (
            empty($arValue['VALUE']['STRING']) &&
            empty($arValue['VALUE']['FILE']['name']) &&
            empty($arValue['VALUE']['TEXTAREA']['TEXT'])
        ) {
            return;
        }

        // Удаляем файл, если отметили галочку
        if (!empty($arValue['VALUE']['DELETE_FILE'])) {
            if (!empty($arValue['VALUE']['SAVED_FILE_ID'])) {
                CFile::Delete($arValue['VALUE']['SAVED_FILE_ID']);
            }
        } // сохраняем файл, если добавили
        elseif (!empty($arValue['VALUE']['FILE']['name'])) {
            $fileId = CFile::SaveFile($arValue['VALUE']['FILE'], 'custom-property');
            $arValue['VALUE']['FILE_ID'] = $fileId;
        } // если уже есть сохраненный файл, то получаемый ID сохраненного файла
        elseif (!empty($arValue['VALUE']['SAVED_FILE_ID'])) {
            $arValue['VALUE']['FILE_ID'] = $arValue['VALUE']['SAVED_FILE_ID'];
        }

        unset($arValue['VALUE']['FILE']);
        unset($arValue['VALUE']['SAVED_FILE_ID']);

        $arValue["VALUE"] = serialize($arValue["VALUE"]);

        return $arValue;
    }

    public static function ConvertFromDB($arProperty, $arValue)
    {
        $arValue["VALUE"] = unserialize($arValue["VALUE"]);

        return $arValue;
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields): void
    {
        $arPropertyFields = array(
            "HIDE" => array('DEFAULT_VALUE'),
        );
    }
}