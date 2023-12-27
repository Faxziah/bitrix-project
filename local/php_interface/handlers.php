<?php

AddEventHandler("iblock", "OnIBlockPropertyBuildList", ["CustomProperty", "GetUserTypeDescription"]);

class CustomProperty
{
    private static $arInputsValue = [];

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
            'PROPERTY_TYPE' => \Bitrix\Iblock\PropertyTable::TYPE_STRING,
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'], // Открытие карточки элемента инфоблока
            "GetSettingsHTML" => [__CLASS__, "GetSettingsHTML"], // Открытие настроек свойства в настройках инфоблока
//            "PrepareSettings" => [__CLASS__, "PrepareSettings"], // Сохранение свойства в настройках инфоблока
            'ConvertToDB' => [__CLASS__, "ConvertToDB"], // Сохранении свойства в карточке элемента (b_iblock_element_property)
            'ConvertFromDB' => [__CLASS__, "ConvertFromDB"], // Извлечение свойства в карточку элемента (b_iblock_element_property)
        ];
    }

    public static function GetPropertyFieldHtml($arProperty, $arValue, $arHtmlControl)
    {
        \CModule::IncludeModule("fileman");

        self::setInputsValue($arValue['VALUE']);
        $html = self::getInputsHtml($arHtmlControl);
        return $html;
    }

    private static function setInputsValue($value)
    {
        self::$arInputsValue = [
            'STRING' => '',
            'FILE' => '',
            'TEXTAREA' => []
        ];

        if (!is_array($value)) {
            return;
        }

        if (!empty($value['STRING'])) {
            self::$arInputsValue['STRING'] = $value['STRING'];
        }

        if (!empty($value['FILE'])) {
            self::$arInputsValue['FILE'] = $value['FILE'];
        }

        if (!empty($value['TEXTAREA'])) {
            self::$arInputsValue['TEXTAREA'] = $value['TEXTAREA'];
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

        $html .= '<br><p>Файл:</p>';

        $fileInput = CFile::InputFile(
            $inputBaseName . '[FILE]',
            20,
            self::$arInputsValue['FILE'],
            strFileType: '',
            bShowFilePath: false
        );

        $html .= $fileInput . '<br><br>';
        $html .= '<label for="custom-textarea">HTML/TEXT:</label>';
        $html .= self::getEditorHtml($arHtmlControl);
        $html .= '<br><br><br>';

        return $html;
    }

    private static function getEditorHtml($arHtmlControl)
    {
        ob_start();
        CFileMan::AddHTMLEditorFrame(
            $arHtmlControl['VALUE'] . '[TEXTAREA][TEXT]',
            self::$arInputsValue['TEXTAREA']['TEXT'] ?? '',
            $arHtmlControl['VALUE'] . '[TEXTAREA][TYPE]',
            self::$arInputsValue['TEXTAREA']['TYPE'] ?? '',
            array(
                'height' => 450,
                'width' => '100%'
            ),
            textarea_field: 'data-original-name="' . $arHtmlControl['VALUE'] . '[TEXTAREA][TEXT]"'
        );

        ?>
        <script>
            window.addEventListener('load', function() {
                let arTextareas = document.querySelectorAll('.typearea')

                for (let textarea of arTextareas) {
                    textarea.name = textarea.dataset.originalName;
                }
            });
        </script>

        <?php
        return ob_get_clean();
    }

    public static function ConvertToDB($arProperty, $arValue)
    {
        $value = $arValue['VALUE'];

        // НЕ сохранять пустые свойства (важно, если поле множественное)
        if (
            empty($value['STRING']) &&
            empty($value['FILE']['name']) &&
            empty($value['TEXTAREA']['TEXT'])
        ) {
            return;
        }

        if (!empty($value['FILE']['name'])) {
            $fileId = CFile::SaveFile($value['FILE'], 'custom-property');
            $arValue['VALUE']['FILE'] = $fileId;
        }

        $arValue["VALUE"] = serialize($arValue["VALUE"]);

        return $arValue;
    }

    public static function ConvertFromDB($arProperty, $arValue)
    {
        $arValue["VALUE"] = unserialize($arValue["VALUE"]);

        return $arValue;
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        $arPropertyFields = array(
            "HIDE" => array('DEFAULT_VALUE'),
        );
    }
}