<?php
/**
 * Cache Validator
 * 
 * A helper class to mainipulate/validate cacheing.
 * 
 * The code simple ported from my oooold project(eps2004),but works!
 * @author night
 */
class DoggyX_Util_HttpCacheValidator {

	/**
	 * Validate client headers and check wheather to send 304 header.
	 * 
	 * @param int $lastModified timestamp to validate
	 * @param string $tag ETag to validate
	 */
	public static function is_expired($lastModified,$tag,$headers){		
    	$refresh=TRUE;
    	if(isset($headers["If-Modified-Since"])) {
			$arraySince = explode(";", $headers["If-Modified-Since"]); 
			$since = strtotime($arraySince[0]); 
			if($since >= $lastModified) $refresh=FALSE;
    	}			
		/**
		 * Check Entity Tag(ETag)
		 * 
		 * Entity tags are used for comparing two or more entities from the same requested resource. 
		 * HTTP/1.1 uses entity tags in the ETag (section 14.19), If-Match (section 14.24), 
		 * If-None-Match (section 14.26), and If-Range (section 14.27) header
		 * fields. The definition of how they are used and compared as cache
		 * validators is in section 13.3.3. An entity tag consists of an opaque
		 * quoted string, possibly prefixed by a weakness indicator. 
		 * 
		 * entity-tag = [ weak ] 
		 * opaque-tag weak       = "W/" 
		 * opaque-tag =quoted-string 
		 * 
		 * A "strong entity tag" MAY be shared by two entities of
		 * a resource only if they are equivalent by octet equality. A "weak
		 * entity tag," indicated by the "W/" prefix, MAY be shared by two
		 * entities of a resource only if the entities are equivalent and could
		 * be substituted for each other with no significant change in
		 * semantics. A weak entity tag can only be used for weak comparison.An
		 * entity tag MUST be unique across all versions of all entities
		 * associated with a particular resource. A given entity tag value MAY
		 * be used for entities obtained by requests on different URIs. The use
		 * of the same entity tag value in conjunction with entities obtained by
		 * requests on different URIs does not imply the equivalence of those
		 * entities.
		 * 
		 * See HTTP/1.1(W3C)
		 * 
		 */
		if(isset($headers["If-None-Match"])) { // check ETag 
            if(strcmp($headers["If-None-Match"], $tag) == 0 ){
                $refresh=FALSE; 
            }
            else {
                $refresh=TRUE; 
            }
             
		}
		if(isset($headers["If-Match"])) { // check ETag 
		  if(strcmp($headers["If-Match"], $tag) == 0 ) 
		     $refresh=FALSE; 
		  else  
		     $refresh=TRUE; 
		}
		//firefox style,resume download
		if(isset($headers["If-Range"])) { // check ETag 
		  if(strcmp($headers["If-Range"], $tag) == 0 ) 
		     $refresh=FALSE; 
		  else  
		     $refresh=TRUE; 
		}
		return $refresh;		
	}
}