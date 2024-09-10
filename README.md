# Contract Management Module

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

