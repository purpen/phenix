<?php
class WareWriteUpdateWareSaleAttrvalueAliasRequest
{
	private $apiParas = array();
	
	public function getApiMethodName(){
	  return "jingdong.ware.write.updateWareSaleAttrvalueAlias";
	}
	
	public function getApiParas(){
		return json_encode($this->apiParas);
	}
	
	public function check(){
		
	}
	
	public function putOtherTextParam($key, $value){
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
                                                        		                                    	                        	                        	                                                    	                        	                   			private $wareId;
    	                        
	public function setWareId($wareId){
		$this->wareId = $wareId;
         $this->apiParas["wareId"] = $wareId;
	}

	public function getWareId(){
	  return $this->wareId;
	}

                                                 	                        	                                                                                                                                                                                                                                                                                                               private $skuAttrId;
                              public function setSkuAttrId($skuAttrId ){
                 $this->skuAttrId=$skuAttrId;
                 $this->apiParas["skuAttrId"] = $skuAttrId;
              }

              public function getSkuAttrId(){
              	return $this->skuAttrId;
              }
                                                                                                                                                                                                                                                                                                                                              private $skuAttrValues;
                              public function setSkuAttrValues($skuAttrValues ){
                 $this->skuAttrValues=$skuAttrValues;
                 $this->apiParas["skuAttrValues"] = $skuAttrValues;
              }

              public function getSkuAttrValues(){
              	return $this->skuAttrValues;
              }
                                                                                                                }





        
 

