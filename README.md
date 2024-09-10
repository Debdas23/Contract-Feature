# Contract Management Module
#Requirement
Contract feature

There needs to be a new tab under Organization called Contracts.  You should be able to add a contract.  Adding a new contract, the user will be asked to upload a Word document with their template.  

Create a new contract: Create a contract, give it a name and upload a document. 

Scan the document: The word document will include variables which are wrapped in double curly braces.  For example {{first_name}} or {{First Name}}.  Some variables will be repeated many times over in the Word document.  So you need to find all unique variables in the document. Also warn the user if there are any issues parsing the document.

Create fields for each variable: For each of the unique variables in the doc, we need to create a field for the contract. Show the name that appeared in the document in the curly braces and then allow the user to enter:
Display field title
Field type (text, number, date, email, select list)
Help text
Default value
If the field type is a select list, allow the user to enter options for that list

Creating a matter: When creating a matter for a user that’s part of an organisation, we will offer them the option of selecting a contract.  This can be part of the new matter wizard.  When they select the contract, we will expose the fields and the user will need to enter those fields.  Then the matter gets created as normal and these fields are linked to the matter as part of the contact request.

Document automation step: When creating the matter, generate the completed document in Word format and put it under a new tab called Contract. For now, only lawyers will be able to see and download the contract.  An email needs to be sent to the lawyer telling them that the contract is 

Review before publishing: There should be a setting for the contract feature which dictates whether contracts are reviewed before they are visible to the user that requested the matter.  If it's switched on then the requester will automatically see the contract and they can download it in doc format.  If not, the lawyer first needs to see it.  They will have the option to edit (using the upcoming doc editing functionality).  Then when they are happy, the can publish at which point the requester will see the document too.



## Overview

The **Contract Management** module for Drupal 10 allows organizations to manage contracts efficiently. Users can upload contract templates (in `.docx` format), extract variables from the documents, configure dynamic fields, and generate completed contract documents for clients. 

### Features
- **Contract Template Upload**: Upload `.docx` contract templates.
- **Variable Extraction**: Automatically scans and identifies variables enclosed in `{{double curly braces}}` within the uploaded contract templates.
- **Dynamic Form Generation**: Based on the extracted variables, dynamic fields are generated, allowing users to input specific details for each contract.
- **Document Automation**: After filling out the form, the module generates a completed contract document by replacing the variables in the template with the provided values.
- **Multi-step Form Support**: The module supports multi-step forms to guide users through the contract creation process.

## Installation

1. **Download and Install the Module**:
    - Clone or download the `contract_management` module into your Drupal `modules/custom/` directory.
    - Enable the module via the Drupal UI or using Drush:
      ```bash
      drush en contract_management
      ```

2. **Install Required Libraries**:
    - This module uses the `phpoffice/phpword` library to process `.docx` files. Install it via Composer:
      ```bash
      composer require phpoffice/phpword
      ```

3. **Configure File System**:
    - Ensure that Drupal’s file system is set up to handle public files for contract uploads. This module stores files in `public://contract_documents/`.

## Usage

1. **Creating a Contract**:
    - Navigate to the `Contracts` tab under `Organization` to add contract.
    - Upload a `.docx` contract template.
    - The module will scan the document for variables and guide you to configure fields for each variable.
    - url: organization/contracts

2. **Generating Completed Documents**:
    - Once fields are configured, fill in the form with the required information.
    - After submitting the form, the module generates a new `.docx` file with the variables replaced by the provided values.
    - You can download the final contract document.

## Requirements

- **Drupal 10.x** 
- **PHP 8.0+**
- **Composer**: Install necessary PHP libraries via Composer.
- **PhpOffice/PhpWord**: Used for reading and writing `.docx` files.

