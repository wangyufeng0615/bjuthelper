<?php
/**
 * Created by PhpStorm.
 * User: remini
 * Date: 2019/2/24
 * Time: 11:05 PM
 */

function get_content_by_tag_and_id(string $content, string $tag, string $id){
    preg_match('/<'.$tag.' (.*?)id=\"'.$id.'\"(.*?)>(.*?)<\/'.$tag.'>/is', $content, $mat);
    return $mat[3];
}

function divide_string_by_colon(string $content){
    $item = str_replace(array("："),':',$content);
    return explode(":", $item);
}
