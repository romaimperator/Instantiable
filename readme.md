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
    $     [i_username] => users_username,
    $     [i_email] => users_email,
    $     [i_Post] => PostObject => (
    $         [i_subject] => post_subject,
    $         [i_body] => post_body
    $     )
    $ )
Note: In order to avoid name collisions with attributes of the model all associated models and model column names are prepended with a lowercase 'i_'.
Also if the user had more than one post returned the i_Post key would point to an array of PostObjects.

Now you can do the following:
    $ $user = $this->User->find('first', array('Instantiable' => true));
    $ echo $user->i_username;
    $ echo $user->i_email;
