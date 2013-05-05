# Custom Lessons, user guide

## Overview

This activity is an enhanced version of the standard Lesson module.
Each original feature is also present in Custom Lesson.


## Customize the lessons

The customization process has 2 steps:

* create the content template,
* import the individual data.

### Create the content template

Here, we have to create a lesson in which some items will be replaced
by individual data.
The replaced elements can be located in all the questions items:

* the **Page contents** field,
* the **Answer** fields,
* the **Feedback** fields.

The elements to be replaced must follow the syntax `[parameter_name]`.
It's recommended to use prefixes to categorize the parameters.
For example, the content of a question could be:

    Which is the complexity for the algorithm [q:algo] ?

### Importat individual data

On the lesson modification page, a new link is available:
"Import individual data".

![Import](images/import-arrow.png)

This link leads to an upload form, to provide a CSV file.
After the upload, a diagnostic page displays the import results.
The import is effective only if the file is validated.

### CSV file format

* The first row contains the column headers
* One of the header cells (generally the first one) must be **userid** or **username**
* The other headers are the names of the parameters to substitute
* The order has no significance
* The cell content must be surrounded by quotes "".

For example, to replace the strings `[q:algo]` and `[r:complexity]`
for two students, with the usernames "john" and "jean":

    "username";"q:algo";"r:complexity"
    "john";"bubble sort";"n^2"
    "jean";"merge sort";"n log n"

The other users will see the original parameters, without any substitution.
