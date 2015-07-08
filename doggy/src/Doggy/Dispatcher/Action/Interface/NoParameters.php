<?php
/**
* This marker interface should be implemented by actions that do not want any
* parameters set on them automatically (by the Doggy_Dispatcher_Interceptor_Parameters)
* This may be useful if one is using the action tag and want to supply
* the parameters to the action manually using the param tag.
* It may also be useful if one for security reasons wants to make sure that
* parameters cannot be set by malicious users.
*/
interface Doggy_Dispatcher_Action_Interface_NoParameters{
}
?>