# Luminix Backend Workbench

This is the testing application for the Luminix Backend package. It is a simple Laravel application with a few models to test the package.

## Models

 - User: A default laravel user model, with relationship to the `ToDo` model.
 - ToDo: A simple model with a title and a description and a boolean to indicate if it is done. Belongs to a user.

For the testing scenario, the following rules will be enforced:

Users can be created by anyone.
Users cannot read, update or delete other users.
Only authenticated users can create ToDos.
ToDos can be read, updated or deleted by their owner.

These rules are created in the `WorkbenchServiceProvider` class. The testing app will not apply `auth` middleware to the routes, relying on `Gate` definitions to enforce the rules.

The testing will follow the following steps:

1. Basic CRUD
    a. Create a user
    b. Create a ToDo for that user
    c. Mark the ToDo as done
    d. Delete the ToDo
    e. Delete the user
2. Authentication
    a. Verify that users cannot read or change other users
    b. Verify that only authenticated users can create ToDos
    c. Verify that users cannot read or change other users ToDos
