<?php
/**
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/9
 * Time: 4:02 PM
 */

/**
 * Class Course 用于记录获取所有课程时单个课程的Bean。顺序按数组顺序排列
 */
class Course
{
    public $id;         //课程代码
    public $name;       //课程名称
    public $type;       //课程性质
    public $credit;     //学分
    public $score;      //最高成绩值
    public $belong;     //课程归属
    public $year;       //学年
    public $term;       //学其
}
