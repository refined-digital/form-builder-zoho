<?php

namespace RefinedDigital\FormBuilderZoho\Module\Classes;

use RefinedDigital\CMS\Modules\Core\Models\EmailSubmission;
use RefinedDigital\FormBuilder\Module\Contracts\FormBuilderCallbackInterface;

class Process implements FormBuilderCallbackInterface {

  public function run($request, $form)
  {

    $formBuilderRepository = new FormBuilderRepository();
    $formBuilderRepository->compileAndSend($request, $form);

    $url = 'https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8';
    $fields = [];

    if (isset($form->fields) && $form->fields->count()) {
      foreach ($form->fields as $field) {
        if ($field->merge_field) {
          $fields[$field->merge_field] = $request[$field->field_name];
        }
      }
    }

    $curl = curl_init($url);
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
    curl_exec( $curl );

    // log a copy of it
    if (sizeof($fields)) {
      $data = new \stdClass();
      $data->data = $request->toArray();
      $submissionData = [
        'to'      => 'salesforce',
        'from'    => 'salesforce',
        'ip'      => help()->getClientIP(),
        'form_id' => isset($form->id) ? $form->id : null,
        'data'    => $data
      ];

      EmailSubmission::create($submissionData);
    }
  }
}
