<?php

namespace Yashwantsb\Formbuilder\Http\Controllers;

use App\Http\Controllers\Controller;

class FormBuilderController extends Controller
{
    public function index()
    {
        return view('FormBuilder::index');
    }
}