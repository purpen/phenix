<?php
/**
 *
 * This interface is implemented by actions that want to declare acceptable parameters. Works in conjunction with {@link
 * Doggy_Dispatcher_Interceptor_Parameters}. For example, actions may want to create a whitelist of parameters they will accept or a
 * blacklist of paramters they will reject to prevent clients from setting other unexpected (and possibly dangerous)
 * parameters.
 */
interface Doggy_Dispatcher_Action_Interface_ParameterNameAware{
}
?>