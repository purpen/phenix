{smarty_plugin doggy_util_smarty_MockPlugin,mock}

{smarty_postfilter mock_trim}
{smarty_include test.smarty.header}
Hello world
{mock_checkDate }
{mock_check_foo }
{doggy_util_smarty_MockPlugin_check_call}
{'chinavisual.com'|mock_url}
{mock_list}
List
{/mock_list}

{mock_timestamp}
{* flow line is test tag parse bug before r4491 *}
{assign var="bugtest" value="if ok should passed" }
{* test tag parse bug before r4878 *}
{html_image file="" } 
{smarty_include test.smarty.footer}

{smarty_outputfilter mock_protectEmail}