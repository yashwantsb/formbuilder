<?php

Route::group(['namespace'=>'Yashwantsb\Formbuilder\Http\Controllers'], function(){
    Route::get("formbuilder", 'FormBuilderController@index');
});
