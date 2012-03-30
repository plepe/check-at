<?php
// from http://at2.php.net/manual/en/function.natsort.php#107199
/**
 * keyNatSort does a natural sort via key on the supplied array.
 *
 * @param   $array      The array to natural sort via key.
 * @param   $saveMemory If true will delete values from the original array as it builds the sorted array.
 * @return  Sorted array on success. Boolean false if sort failed or null if the object was not an array.
 */
function keyNatSort($array, $saveMemory=false)
{
    if(is_array($array))
    {
        $keys = array_keys($array);
        if(natsort($keys))
        {
            $result = array();
            foreach($keys as $key)
            {
                $result[$key] = $array[$key];
                if($saveMemory)
                    unset($array[$key]);
            }
               
        }
        else
            $result = false;
    }
    else
        $result = null;
   
    return $result;
}
?> 
