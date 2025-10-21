## Application Form

**Module:** PHP Basics  
**Goal:** Form Validation and Error Handling  
**Status:** ✅ Done   
**Version:** 1.0  
**Technologies:** PHP, Bulma  

## Description
This exercise focused on building an application form (`index.php`) with multiple required fields.  
All inputs had to be validated using PHP to ensure data correctness, display meaningful error messages, and provide visual feedback when invalid data was entered.  

Validated fields:
- First Name (min. 2 characters)  
- Last Name (min. 2 characters)  
- E-Mail (valid format)  
- ZIP Code (numeric)  
- City  
- Region  
- Birth Date (minimum age: 18 years)  
- CV (PDF upload)  
- General Terms and Conditions (checkbox)  

On successful validation, all user data—including the file path to the uploaded CV—had to be saved in a CSV file, and the PDF stored in a separate folder without overwriting existing files.

## Main Goal
Server-side validation and error handling in PHP, including file upload management and CSV storage.
