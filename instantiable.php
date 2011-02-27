<?php
/**
 * Behavior to instantiate models from returned data.
 *
 * This behavior allows the user to be returned model instances that contain
 * the data instead of returning an array of data.
 *
 * Copyright 2011, Daniel Fox
 *
 * Licensed under The MIT License
 * Reditributions of files must retain the above copyright notice.
 *
 * @copyright      Copyright 2011, Daniel Fox
 * @license        MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InstantiableBehavior extends ModelBehavior {
    /**
     * If true will always instantiate models for every query with or without 
     * the find parameter. If false it will still instantiate models but only 
     * if the query parameters contain the key 'Instantiable' with the value 
     * true.
     *
     * Default is false.
     */
    var $alwaysInstantiate = false;

    /**
     * Here I add the alwaysInstantiate option
     */
    function setup(&$model, $settings = array()) {
        if (isset($settings['alwaysInstantiate']) && $settings['alwaysInstantiate']) {
            $this->alwaysInstantiate = true;
        }
    }

    /**
     * Here I use the beforeFind callback to check if user wants to use the 
     * behavior.
     */
    function beforeFind(&$model, $query) {
        if (isset($query['Instantiable']) && $query['Instantiable']) {
            $model->instantiable = true;
        }
    }

    /**
     * Here I use the afterFind method to actually modify the returned data to 
     * be the created model instances.
     */
    function afterFind(&$model, $results, $primary) {
        if ((isset($model->instantiable) && $model->instantiable && $primary)
            || $this->alwaysInstantiate) {
            $result = $this->create($model->name, $results);
            // Return an array because it must be an array to replace the $results
            return (is_array($result)) ? $result : array($result);
        }
    }

    /**
     * Returns model instances created from the supplied data.
     *
     * If the supplied data is an array of records then it returns an array of
     * models. If the supplied data is an array of a single record or is an 
     * array of the data from a single record then it returns a single model 
     * instance.
     *
     * This function also checks and creates model instances for related models
     * using their create function.
     *
     * @param $data the array of data to convert
     * @return either a single model instance or array of instances
     */
    function create($name, $data) {
        // Check whether this is an array of model data or a single record
        if (!isset($data[0])) { // If there isn't a 0 index then it is not an array so make it an array.
            $data = array(0 => $data);
            $was_array = false;
        } elseif (count($data) == 1) { // If there is only one record then it is not an array.
            $was_array = false;
        } else { // Otherwise it is an array.
            $was_array = true;
        }

        // Create the temporary array to hold the instances of the data
        $output = array();

        //debug($data);
        // Loop through each of supplied records and produce a model instance 
        // for each one of them.
        foreach($data as $record) {
            // Retrieve an instance of the model we are in
            $instance = $this->getModel($name);

            // Loop through each column of the model and check if there is 
            // supplied data for that column.
            foreach($instance->schema() as $column => $type) {
                // Is the column in the data? If so add the value as a attribute of the model.
                if (isset($record[$instance->name][$column])) { // Is this array in [Model][Column] form?
                    $instance->{'i'.$column} = $record[$instance->name][$column];
                } elseif (isset($record[$column])) { // Or is it just in [Column] form?
                    $instance->{'i'.$column} = $record[$column];
                } else { // Or is there no data for this column?
                    $instance->{'i'.$column} = null;
                }
            }

            // Now continue by checking for related model data
        
            // Check the hasOne relationships
            $this->check_and_add_model($instance, 'hasOne', $record);
            
            // Check the hasMany relationships
            $this->check_and_add_model($instance, 'hasMany', $record);

            // Check the belongsTo relationships
            $this->check_and_add_model($instance, 'belongsTo', $record);

            // Check the hasAndBelongsToMany relationships
            $this->check_and_add_model($instance, 'hasAndBelongsToMany', $record);

            // Add instance that was just created to the finished array of 
            // model instances.
            $output[] = $instance;
        }

        // If the supplied data was an array then return the array of new model 
        // instances
        if ($was_array) {
            return $output;
        } else { // If not or an array of a single record then return only that new model
            return $output[0];
        }
    }

    /**
     * Loops through the models relationships and checks if theres data for 
     * them. If so it creates a new attribute for the model and recurses 
     * creating model instances for the related models.
     *
     * @param &$instance the model instance to modify
     * @param $relationship a string for the type of relationship
     * @param $record the data to add
     * @return nothing
     */
    function check_and_add_model(&$instance, $relationship, $record) {
        // Loop through each relationship and check if there is supplied data.
        foreach($instance->{$relationship} as $model => $params) {
            // Is there supplied data for this model?
            if(isset($record[$model])) { // If so then pass the data for that model to the model to create a new instance of the related model.
                $instance->{'i'.$model} = $this->create($model, $record[$model]);
            } elseif (!empty($record[$model])) { // If not then set the value to null
                $instance->{'i'.$model} = null;
            }
        }
    }

    // The following code is written by Felix Geisendörfer from
    // http://debuggable.com/posts/how-to-properly-create-a-model-instance-manually:480f4dd6-4424-4c89-9564-4647cbdd56cb
    // and is not written by me. Exception is the first if statement the second 
    // condition was modified by me. Also modified the code so instead of 
    // returning the already created object instance it will always create a 
    // new instance.
    function &getModel($model)
    {
        // Make sure our $modelClass name is camelized
        $modelClass = Inflector::camelize($model);

        // If the Model class does not exist and we cannot load it
        if (!class_exists($modelClass) && !ClassRegistry::init($modelClass))
        {
            // Can't pass false directly because only variables can be passed via reference
            $tmp = false;
            
            // Return false
            return $tmp;
        }
        
        // The $modelKey is the underscored $modelClass name for the ClassRegistry
        $modelKey = Inflector::underscore($modelClass);
        
        // If the ClassRegistry holds a reference to our Model
        //if (ClassRegistry::isKeySet($modelKey))
        //{
            // Then make this our $ModelObj
            //$ModelObj =& ClassRegistry::getObject($modelKey);
        //}
        //else
        //{
            // If no reference to our Model was found in trhe ClassRegistry, create our own one
            $ModelObj =& new $modelClass();
            
            // And add it to the class registry for the next time
            //ClassRegistry::addObject($modelKey, $ModelObj);   
        //}

        // Return the reference to our Model object
        return $ModelObj;
    }
}
