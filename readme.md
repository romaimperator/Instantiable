Installation
------------

1. Either clone the repository or download the behavior and copy instantiable.php to app/models/behaviors/
2. Next either add the behavior to the app_model or to each model you would like to use it in.
    $ var $actAs = array('Instantiable');

Usage
-----

To have a call to find return model objects instead just add 'Instantiable' => true to the parameters:
    $ $this->User->find('first', array('Instantiable' => true));

If you would like every call to find to return model objects just add the alwaysInstantiate value when you add the behavior like so:
    $ var $actAs = array('Instantiable' => array('alwaysInstantiate' => true));
Note: This will likely cause issues if you have baked your views since they will be looking for a data array instead of a model object.

Then given a user model who's normal data from this find:
    $ $this->User->find('first');
would look like this:
    $ array(
    $    'User' => array(
    $        'username',
    $        'email'
    $    ),
    $    'Post' => array(
    $        'subject',
    $        'body'
    $    )
    $ )
the model object array would now look like this:
    $ UserObject => (
    $     [iusername] => users_username,
    $     [iemail] => users_email,
    $     [iPost] => PostObject => (
    $         [isubject] => post_subject,
    $         [ibody] => post_body
    $     )
    $ )
Note: In order to avoid name collisions with attributes of the model all associated models and model column names are prepended with a lowercase 'i'.
Also if the user had more than one post returned the Post key would point to an array of PostObjects.

Now you can do the following:
    $ $user = $this->User->find('first', array('Instantiable' => true));
    $ echo $user->username;
    $ echo $user->email;

Details
-------

This behavior works by creating base models from the model type the find is called from. For example, given a call to find like this:
    $ $users = $this->User->find('all', array('Instantiable' => true));
then $users is of the same type as the User model.
Also, if the find returns more than one type of user than $users will be an array of User model objects.

For each of the base models the behavior loops through all of the table columns given by $model->schema() and checks if the find data has that column. If it does then the value is assigned to an attribute named i<column name>. So for example value of the column name username would then be assigned to a new model attribute named iusername.

Next after creating attributes for each column, each association type is checked for related data. For example if the User model has a hasMany relationship to the Post model then while processing the User model find data it will check if the find has also returned data from the Post model. If there is data for the Post model it recurses running through and creating a Post model which of course then checks for Post column names and then Post associations.

Once all models have been created, either a single model object (with any associated data stored in an attribute) or an array of models will be returned from the find method.

Note: Even if the data array would normally contain an array with a single element such as when using find('all') this behavior returns that as a single model NOT an array. Yes I'm thinking about changing this.
