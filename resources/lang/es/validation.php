<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The field must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'filled' => 'The :attribute field is required.',
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values is present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The field has already been taken.',
    'url' => 'The :attribute format is invalid.',
    'substring' => 'The :tag tag was not found in :attribute.',
    'license' => 'The license is not valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'miss_main_field_tag' => [
            'required' => 'Falta la etiqueta de campo EMAIL',
        ],
        'conflict_field_tags' => [
            'required' => 'Las etiquetas de campo no pueden ser las mismas',
        ],
        'segment_conditions_empty' => [
            'required' => 'La lista de condiciones no puede estar vacía',
        ],
        'mysql_connection' => [
            'required' => 'No se puede conectar al servidor MySQL',
        ],
        'database_not_empty' => [
            'required' => 'La base de datos no está vacía',
        ],
        'promo_code_not_valid' => [
            'required' => 'El código promocional no es válido',
        ],
        'smtp_valid' => [
            'required' => 'No se puede conectar al servidor SMTP',
        ],
        'yaml_parse_error' => [
            'required' => 'No se puede analizar yaml. Compruebe la sintaxis',
        ],
        'file_not_found' => [
            'required' => 'Archivo no encontrado.',
        ],
        'not_zip_archive' => [
            'required' => 'El archivo no es un paquete zip.',
        ],
        'zip_archive_unvalid' => [
            'required' => 'No se puede leer el paquete.',
        ],
        'custom_criteria_empty' => [
            'required' => 'Los criterios personalizados no pueden estar vacíos',
        ],
        'php_bin_path_invalid' => [
            'required' => 'Ejecutable PHP no válido. Por favor revise de nuevo.',
        ],
        'can_not_empty_database' => [
            'required' => 'No puede eliminar determinadas tablas, por favor, limpie su base de datos y vuelva a intentarlo.',
        ],
        'recaptcha_invalid' => [
            'required' => 'Comprobación reCAPTCHA no válida.',
        ],
        'payment_method_not_valid' => [
            'required' => 'Algo salió mal con la configuración del método de pago. Por favor revise de nuevo.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
