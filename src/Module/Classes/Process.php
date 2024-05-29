<?php

namespace RefinedDigital\FormBuilderZoho\Module\Classes;

use RefinedDigital\CMS\Modules\Core\Models\EmailSubmission;
use RefinedDigital\FormBuilder\Module\Contracts\FormBuilderCallbackInterface;

class Process implements FormBuilderCallbackInterface
{

    public function run($request, $form)
    {
        $url    = 'https://crm.zoho.com.au/crm/WebToLeadForm';
        $fields = [];

        // set the form fields
        if(isset($form->fields) && $form->fields->count()) {
            foreach($form->fields as $field) {
                $key = $field->merge_field;
                if($key) {
                    $value = $request[$field->field_name];
                    if($key === 'Description') {
                        $value = $field->name.': '.$value;
                        if(isset($fields[$key])) {
                            $value = $fields[$key].PHP_EOL.$value;
                        }
                    }
                    $fields[$key] = $value;
                }
            }
        }

        $notAField = ['_token', 'hname', 'htime'];
        // set the hidden fields
        $requestFields = $request->toArray();
        if(sizeof($requestFields)) {
            foreach($requestFields as $field => $value) {
                if(!in_array($field, $notAField) && preg_match_all('/^(field)\d*/', $field) < 1) {
                    $fields[str_replace('__', ' ', $field)] = $value;
                }
            }
        }

        // add the redirect code
        if($form->redirect_page) {
            $fields['returnUrl'] = rtrim(config('app.url'), '/').$form->redirect_page;
        } else {
            $fields['returnUrl'] = rtrim(config('app.url'), '/').'/thank-you';
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);
        curl_close();

        // log a copy of it
        if(sizeof($fields)) {
            $data           = new \stdClass();
            $data->data     = $request->toArray();
            $submissionData = [
                'to'      => 'zoho',
                'from'    => 'zoho',
                'ip'      => help()->getClientIP(),
                'form_id' => isset($form->id) ? $form->id : null,
                'data'    => $data
            ];

            EmailSubmission::create($submissionData);
        }
    }
}
