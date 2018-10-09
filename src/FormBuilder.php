<?php

namespace Yashwantsb\Formbuilder;

use App\Http\Controllers\Controller;

class FormBuilder extends Controller
{
    /**
     * Build form function
     * 
     * @param string $schema form schema json string
     * @return string form html code
    */
    public static function build($schema,$values=null,$formModify=null,$addField=null){
        if(!$schema = json_decode($schema))return 'FORM BUILDER - Error! Input is not in proper json format';
        
        if($error = Self::validateSchema($schema))return $error;

        $form = $formPostMethod = $formFileString = $formFields = '';

        if(!empty($formModify) && $formModify = json_decode($formModify)){
            if(isset($formModify->formName) && $formModify->formName != '')$schema->formName = $formModify->formName;
            if(isset($formModify->formAction) && $formModify->formAction != '')$schema->formAction = $formModify->formAction;
            if(isset($formModify->formMethod) && $formModify->formMethod != '')$schema->formMethod = $formModify->formMethod;
        }

        if(!empty($addField) && $addField = json_decode($addField)){
            $schema->fields = (object)array_merge((array)$schema->fields,(array)$addField);
        }

        if(isset($schema->formMethod) && in_array(strtoupper($schema->formMethod), ['GET','POST'])){
            $formPostMethod = strtoupper($schema->formMethod);
        }

        foreach($schema->fields as $field){
            if(strtolower($field->fieldType) == "file")$formFileString = 'enctype="multipart/form-data"';
        }

        $form .= '<form name="'.$schema->formName.'" id="'.$schema->formName.'" action="'.url($schema->formAction).'" method="'.$formPostMethod.'" '.$formFileString.'>';
        $form .= csrf_field();

        if(count((array)$schema->fields)){
            foreach($schema->fields as $field){                
                $formField = "";

                if(isset($values[$field->fieldName]) && $values[$field->fieldName] != ""){
                    $inputValue = $values[$field->fieldName];
                }elseif(isset($field->fieldValue) && $field->fieldValue != ""){
                    $inputValue = $field->fieldValue;
                }else $inputValue = '';
                
                switch(strtolower($field->fieldType)){
                    case "text" :
                        $formField .= Self::buildFieldInput($field,$inputValue);
                        break;
                    case "email" :
                        $formField .= Self::buildFieldInput($field,$inputValue);
                        break;
                    case "password" :
                        $formField .= Self::buildFieldInput($field,$inputValue);
                        break;
                    case "date" :
                        $formField .= Self::buildFieldInput($field,$inputValue);
                        break;
                    case "number" :
                        $formField .= Self::buildFieldInput($field,$inputValue);
                        break;
                    case "hidden" :
                        $formField .= Self::buildFieldHidden($field,$inputValue);
                        break;
                    case "select" :
                        $formField .= Self::buildFieldSelect($field,$inputValue);
                        break;
                    case "textarea" :
                        $formField .= Self::buildFieldTextarea($field,$inputValue);
                        break;
                    case "radio" :
                        $formField .= Self::buildFieldRadio($field,$inputValue);
                        break;
                    case "checkbox" :
                        $formField .= Self::buildFieldCheckbox($field,$inputValue);
                        break;
                    case "file" :
                        $formField .= Self::buildFieldFile($field,$inputValue);
                        break;
                    case "sectionheader" :
                        $formField .= Self::buildSectionHeader($field);
                        break;
                    case "sectiondivider" :
                        $formField .= Self::buildSectionDivider($field);
                        break;
                }
                
                $columnWidth = 12;
                if(isset($field->column) && is_numeric($field->column)){
                    if($field->column > 0 && $field->column < 12) $columnWidth = $field->column;
                    else $columnWidth = 12;
                }
                $formFields .= '<div class="col-sm-'.$columnWidth.'">'.$formField.'</div>';
            }

        }
        
        $form .= '<div class="row">'.$formFields.'</div>';
        $form .= '<div class="form-group text-right mb-0">';

        if(isset($schema->actions) && count((array)$schema->actions)){
            foreach($schema->actions as $btn){
                $btnType = in_array(strtolower($btn->btnType),['button','submit','reset']) ? $btn->btnType : 'button';
                $btnClass = strtolower($btn->btnType) == 'submit' ? "btn-success" : (
                    strtolower($btn->btnType) == 'reset' ? "btn-warning" : "btn-default");
                $form .= '<button type="'.$btnType.'" class="btn btn-sm '.$btnClass.'">'.$btn->btnDisplayName.'</button> ';
            }
        }else{
            $form .= '<button type="submit" class="btn btn-sm btn-success">Submit</button>
                <button type="reset" class="btn btn-sm btn-warning">Reset</button>';
        }
        $form .= '</div></form>';
        return $form;
    }

    private static function validateSchema($schema){
        if(empty($schema->formName) || !preg_match('/^[a-zA-Z]+[a-zA-Z0-9]+$/',$schema->formName)){
            return 'FORM BUILDER - Error! Form name can not be blank and it contain only aplhabates, numbers';
        }

        if(empty($schema->formAction)){
            return 'FORM BUILDER - Error! Form action not defined';
        }

        if(!isset($schema->fields)){
            return 'FORM BUILDER - Error! There is no input fields, atleast one input field is required.';
        }

        foreach ($schema->fields as $field) {
            if(empty($field->fieldName)){
                return 'FORM BUILDER - Error! Field name can not be blank';
            }
            if(empty($field->fieldType)){
                return 'FORM BUILDER - Error! Field type can not be blank';
            }
            // if($field->fieldType == "select"){
                // echo empty($field->fieldValueVariable);exit;
                // if(empty($field->fieldValues) && empty($field->fieldValueVariable)){
                //     return 'FORM BUILDER - Error! In select input field values can not be blank';
                // }
            // }
        }

        return false;
    }

    private static function buildFieldInput($schema,$values=null){
        if(empty($schema->fieldType))$fieldType = "text";
        else{
            if(in_array(strtolower($schema->fieldType), ['text','email','password','date','number']))$fieldType = $schema->fieldType;
            else $fieldType = "text";
        }

        if(!isset($schema->fieldDisplayName) && empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;

        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";
        
        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>
            <input type="text" class="form-control '. ($fieldType == 'date' ? 'datepicker' : '') .'" id="'.$schema->fieldName.'" name="'.$schema->fieldName.'" placeholder="'.$fieldDisplayName.'" value="'. (old($schema->fieldName) ? old($schema->fieldName) : $values) .'" '.$fieldRequired.'>
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildFieldHidden($schema,$values=null){       
        $form = '<input type="hidden" id="'.$schema->fieldName.'" name="'.$schema->fieldName.'" value="'. (old($schema->fieldName) ? old($schema->fieldName) : $values) .'">';
        return $form;
    }

    private static function buildFieldSelect($schema,$values=null){
        if(empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;
        
        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";

        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        if(!empty($schema->multiSelect) && $schema->multiSelect == 1){
            $fieldMultiSelect = "multiple";
            if(!empty($schema->size) && is_numeric($schema->size) && $schema->size != "")$fieldMultiSelect .= ' size="'.$schema->size.'"';
            else $fieldMultiSelect .= ' size="'. 3 .'"';
        }else $fieldMultiSelect = "";

        $dropdownFromDataMaster = 0; $selectedValue = '';
        if(!empty($schema->fieldValue)){
            if(!empty($schema->fieldValue->options)) $dropdownOptions = $schema->fieldValue->options;
            if(!empty($schema->fieldValue->selected)) $selectedValue = $schema->fieldValue->selected;
        }
        if(!empty($schema->fieldVariable) && file_exists(storage_path().'/app/dropdownList.json')){
            $dropdownList = json_decode(file_get_contents(storage_path().'/app/dropdownList.json'), true);
            if(isset($dropdownList[$schema->fieldVariable]))$dropdownOptions = $dropdownList[$schema->fieldVariable];
        }
        if(is_array($values)){
            if(isset($values['options'])){ $dropdownOptions = $values['options']; $dropdownFromDataMaster = 1; }
            if(isset($values['selected'])) $selectedValue = $values['selected'];
        }
        if(old($schema->fieldName)) $selectedValue = old($schema->fieldName);

        $selectedValue = explode(',',$selectedValue);

        $options = '';
        if(isset($dropdownOptions)){
            if($dropdownFromDataMaster == 1){
                foreach ($dropdownOptions as $key => $value) {
                    $options .= '<option value="'.$key.'"'. (in_array($key,$selectedValue) ? " selected" : "") .'>'.$value.'</option>';
                }
            }else{
                foreach ($dropdownOptions as $key => $value) {
                    $options .= '<option value="'.$value.'"'. (in_array($value,$selectedValue) ? " selected" : "") .'>'.$value.'</option>';
                }                
            }
        }

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>
            <select class="form-control" id="'.$schema->fieldName.'" name="'.$schema->fieldName. ($fieldMultiSelect ? "[]" : ""). '" '.$fieldRequired.' '.$fieldMultiSelect.'>
                <option value="">Select '.$fieldDisplayName.'</option>'.$options.'
            </select>
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildFieldTextarea($schema,$values=null){
        if(empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;

        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";
        
        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        if(isset($schema->lines) && is_numeric($schema->lines))$lines = $schema->lines;
        else $lines = 3;

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>
            <textarea class="form-control" id="'.$schema->fieldName.'" name="'.$schema->fieldName.'" rows="'.$lines.'" '.$fieldRequired.'>'.(old($schema->fieldName) ? old($schema->fieldName) : $values).'</textarea>
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildFieldRadio($schema,$values=null){
        if(empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;
        
        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";

        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        if(isset($schema->fieldValuesInline) && is_numeric($schema->fieldValuesInline) && $schema->fieldValuesInline == 1)$fieldValuesInline = 1;
        else $fieldValuesInline = 0;

        $selectedValue = '';
        if(!empty($schema->fieldValue)){
            if(!empty($schema->fieldValue->options)) $radioOptions = $schema->fieldValue->options;
            if(!empty($schema->fieldValue->selected)) $selectedValue = $schema->fieldValue->selected;
        }
        if(is_array($values)){
            if(isset($values['options'])) $radioOptions = $values['options'];
            if(isset($values['selected'])) $selectedValue = $values['selected'];
        }
        if(old($schema->fieldName)) $selectedValue = old($schema->fieldName);

        $options = '';
        if(isset($radioOptions)){
            if($fieldValuesInline){
                $options .= '<div>';
                foreach ($radioOptions as $key => $value) {
                    $options .= '<label class="radio-inline"><input type="radio" name="'.$schema->fieldName.'" id="" value="'.$key.'"'. (($selectedValue == $key) ? " checked" : "") .'>'.$value.'</label>';
                }
                $options .= '</div>';
            }else{
                foreach ($radioOptions as $key => $value) {
                    $options .= '<div class="radio"><label><input type="radio" name="'.$schema->fieldName.'" id="" value="'.$key.'"'. (($selectedValue == $key) ? " checked" : "") .'>'.$value.'</label></div>';
                }
            }
        }

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>
            '.$options.'
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildFieldCheckbox($schema,$values=null){
        if(empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;
        
        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";

        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        // if(isset($schema->fieldValuesInline) && is_numeric($schema->fieldValuesInline) && $schema->fieldValuesInline == 1)$fieldValuesInline = 1;
        // else $fieldValuesInline = 0;

        // $selectedValue = '';
        // if(!empty($schema->fieldValue)){
        //     if(!empty($schema->fieldValue->options)) $radioOptions = $schema->fieldValue->options;
        //     if(!empty($schema->fieldValue->selected)) $selectedValue = $schema->fieldValue->selected;
        // }
        // if(is_array($values)){
        //     if(isset($values['options'])) $radioOptions = $values['options'];
        //     if(isset($values['selected'])) $selectedValue = $values['selected'];
        // }
        // if(old($schema->fieldName)) $selectedValue = old($schema->fieldName);

        $options = '';
        if(isset($schema->fieldValue->options)){
            // if($fieldValuesInline){
                $options .= '<div>';
                foreach ($schema->fieldValue->options as $key => $value) {
                    $options .= '<label style="font-weight:normal;"><input type="checkbox" name="'.$schema->fieldName.'" id="'.$schema->fieldName.'" value="'.$key.'"> '.$value.'</label> &nbsp; ';
                }
                $options .= '</div>';
            // }else{
            //     foreach ($radioOptions as $key => $value) {
            //         $options .= '<div class="radio"><label><input type="radio" name="'.$schema->fieldName.'" id="" value="'.$key.'"'. (($selectedValue == $key) ? " checked" : "") .'>'.$value.'</label></div>';
            //     }
            // }
        }

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>
            '.$options.'
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildFieldFile($schema,$values=null){
        if(empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;
        
        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))$fieldDisplayNote = $schema->fieldDisplayNote;
        else $fieldDisplayNote = "";
        
        if(!empty($schema->required) && $schema->required == 1)$fieldRequired = "required";
        else $fieldRequired = "";

        $fileInput = '<input type="'.$schema->fieldType.'" class="form-control" id="'.$schema->fieldName.'" name="'.$schema->fieldName.'" placeholder="'.$schema->fieldDisplayName.'" '.$fieldRequired.'>';

        if($values){
            $input = '<div class="input-group mb-1">'.$fileInput.'
                <div class="input-group-btn">
                    <button class="btn btn-default btn-sm" type="button" onclick="fileViewer('.$values.');return false;"><i class="fa fa-eye"></i> View</button>
                    <a href="'.url("helper/getFileById?id=".$values).'" class="btn btn-default btn-sm" target="_blank" ><i class="fa fa-download"></i> Download</a>
                </div>
            </div>';
        }else $input = $fileInput;

        $form = '<div class="form-group form-group-sm">
            <label class="control-label">'.$fieldDisplayName.' '. ($fieldRequired == "required" ? '<span class="text-danger">*</span>' : '') .'</label>'.$input.'
            <span class="help-block">'. ($fieldDisplayNote != "" ? $fieldDisplayNote  : '&nbsp') .'</span>
        </div>';
        return $form;
    }

    private static function buildSectionHeader($schema){
        if(!isset($schema->fieldDisplayName) && empty($schema->fieldDisplayName))$fieldDisplayName = $schema->fieldName;
        else $fieldDisplayName = $schema->fieldDisplayName;
        
        $form = '<p class="lead text-primary" style="border-bottom:1px solid #ccd0d2">'.$fieldDisplayName;

        if(isset($schema->fieldDisplayNote) && !empty($schema->fieldDisplayNote))
            $form .= '<span class="pull-right text-danger"><small>Note: '.$schema->fieldDisplayNote.'</small></span></p>';
        else $form .= '</p>';
        
        return $form;
    }

    private static function buildSectionDivider($schema){
        $form = '<hr/>';
        return $form;
    }
}