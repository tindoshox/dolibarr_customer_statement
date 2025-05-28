# CUSTOMERSTATEMENT FOR [DOLIBARR ERP & CRM](https://www.dolibarr.org)

## Features

This module generates a customer statement that includes all invoice, credit note, and payment transaction for a customer. It adds a new button to the Third party card.
When clicked, the user is presented with a modal to select a date range for the statement.

The statement of saved and available to view/download from the linked files section.

This is a work in progress and functionality may change over time.


![Screenshot customerstatement](img/screenshot_card.png?raw=true "CustomerStatement")

![Screenshot pdf](img/screenshot_statement.png?raw=true "CustomerStatement")


Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files in the module directories under `langs`.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more information, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


## Installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolistore.org](https://www.dolibarr.org).
You can also get a ready-to-use instance in the cloud from https://saas.dolibarr.org


### From the ZIP file and GUI interface

If the module is a ready-to-deploy zip file, so with a name `module_xxx-version.zip` (e.g., when downloading it from a marketplace like [Dolistore](https://www.dolistore.com)),
go to menu `Home> Setup> Modules> Deploy external module` and upload the zip file.

<!--

Note: If this screen tells you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the `htdocs/conf/conf.php` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading `//`) and assign the proper value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
-->

<!--

### From a GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/customerstatement`

```shell
cd ....../custom
git clone git@github.com:gitlogin/customerstatement.git customerstatement
```

-->

### Final steps

Using your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup"> "Modules"
  - You should now be able to find and enable the module



## Licenses

### Main code

GPLv3 any later version. See file COPYING for more information.

### Documentation

All texts and readme's are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).
