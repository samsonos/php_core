<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 15.09.14 at 11:01
 */
 namespace samson\core;

/**
 * Generic object configuration class
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Configurator
{
    /** @var object Pointer to object instance to be configured */
    public $entity;

    /**
     * Constructor
     * @param object $entity Pointer to configured module
     */
    public function __construct(& $entity)
    {
        $this->entity = $entity;

        // Get only nested class variables array, clear empty variables
        $vars = array_filter(array_diff(get_object_vars($this), get_class_vars(__CLASS__)));

        // Iterate all children class variables
        foreach($vars as $var => $value) {
            // If module has configured property
            if (property_exists($entity, $var)) {
                // Set module variable value
                $entity->$var = $value;
            }
        }
    }
}
 