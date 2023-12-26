<?php

AddEventHandler("iblock", "OnIBlockPropertyBuildList", ["CustomProperty", "GetCustomProperty"]);

class CustomProperty
{
    private static $arInputsValue = [];

    /**
     * @return array 'Массив, описывающий кастомное свойство'
     */
    public static function GetCustomProperty(): array
    {
        return [
            'USER_TYPE_ID' => 'custom_property',
            'USER_TYPE' => 'CUSTOM_PROPERTY',
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => 'Кастомное свойство файл+строка+HTML/text',
            'PROPERTY_TYPE' => \Bitrix\Iblock\PropertyTable::TYPE_STRING,
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
//            "GetSettingsHTML" => [__CLASS__, "GetSettingsHTML"],
//            "PrepareSettings" => [__CLASS__, "PrepareSettings"],
            'ConvertToDB' => [__CLASS__, "ConvertToDB"],
            'ConvertFromDB' => [__CLASS__, "ConvertFromDB"],
        ];
    }

    /** Вызывается при открытии карточки элемента инфоблока */
    public static function GetPropertyFieldHtml($arProperty, $arValue, $arHtmlControl)
    {
        $fieldName = htmlspecialcharsbx($arHtmlControl['VALUE']);

        self::setInputsValue($arValue['VALUE']);
        $html = self::getInputsHtml($fieldName);

        return $html;
    }

    private static function setInputsValue($value)
    {
        self::$arInputsValue = [
            'STRING_INPUT' => '',
            'FILE_INPUT' => '',
            'TEXTAREA_INPUT' => ''
        ];

        if (!empty($value) && is_array($value)) {
            if (!empty($value['STRING'])) {
                self::$arInputsValue['STRING_INPUT'] = $value['STRING'];
            }

            if (!empty($value['FILE'])) {
                self::$arInputsValue['FILE_INPUT'] = CFile::GetPath($value['FILE']);
            }

            if (!empty($value['TEXTAREA'])) {
                self::$arInputsValue['TEXTAREA_INPUT'] = $value['TEXTAREA'];
            }
        }
    }

    private static function getInputsHtml($fieldName): string
    {
        $html = '<label for="custom-string">Строка:</label><br>
                <input 
                style="width: 300px;" 
                id="' . $fieldName . '" 
                name="' . $fieldName . '[STRING]" 
                type="text"
                value="' . self::$arInputsValue['STRING_INPUT'] . '">';

        $html .= '<br><p>Файл:</p>';
        $html .= '<span class="adm-input-file" id="test1"><span>Добавить файл</span>
                <input name="' . $fieldName . '[FILE]" 
                class="typefile adm-designed-file"
                id="test"
                size="20" 
                type="file" value="' . self::$arInputsValue['FILE_INPUT'] . '"
                onchange="BX.adminFormTools._modified_file_onchange.call(this)"
                ></span>';

        $html .= '<br><br><label for="custom-textarea">HTML/TEXT:</label><br>';
        $html .= '<textarea 
            class="typearea" 
            id="custom-textarea" 
            style="width:200px; height:100px;" 
            name="' . $fieldName . '[TEXTAREA]" 
            id="custom-textarea"
        >' . self::$arInputsValue['TEXTAREA_INPUT'] . '</textarea>';

        $html .= '<br><br><br>';

        return $html;
    }

    /** Вызывается при сохранении свойства в карточке элемента инфоблока в b_iblock_element_property. Сериализуем
     */
    public static function ConvertToDB($arProperty, $arValue)
    {
        $value = $arValue['VALUE'];

        // НЕ сохранять пустые свойства (важно, если поле множественное)
        if (
            empty($value['STRING']) &&
            empty($value['FILE']['name']) &&
            empty($value['TEXTAREA'])
        ) {
            return;
        }

        $fileId = CFile::SaveFile($value['FILE'], 'custom-property');

        $arValue['VALUE']['FILE'] = $fileId;
        $arValue["VALUE"] = serialize($arValue["VALUE"]);

        return $arValue;
    }

    /** Вызывается при извлечении свойства в карточку элемента инфоблока из b_iblock_element_property. Десериализуем
     */
    public static function ConvertFromDB($arProperty, $arValue)
    {
        $arValue["VALUE"] = unserialize($arValue["VALUE"]);

        return $arValue;
    }
}