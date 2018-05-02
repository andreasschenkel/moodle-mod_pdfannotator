<?php
/**
 * Moodle restores data from course backups by executing so called restore plan.
 * The restore plan consists of a set of restore tasks and finally each restore task consists of one or more restore steps.
 * You as the developer of a plugin will have to implement one restore task that deals with your plugin data. Most plugins
 * have their restore tasks consisting of a single restore step - the one that parses the plugin XML file and puts the data into its tables.
 * 
 * @package   mod_pdfannotator
 * @category  backup
 * @copyright 2018 RWTH Aachen, Anna Heynkes (see README.md)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_pdfannotator_activity_task
 */

/**
 * Structure step to restore one pdfannotator activity
 */
class restore_pdfannotator_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        
        $userinfo = $this->get_setting_value('userinfo'); // is 0
        
        $paths[] = new restore_path_element('pdfannotator', '/activity/pdfannotator');
        
        $paths[] = new restore_path_element('pdfannotator_annotation', '/activity/pdfannotator/annotations/annotation');
        $paths[] = new restore_path_element('pdfannotator_comment', '/activity/pdfannotator/annotations/annotation/comments/comment');
        $paths[] = new restore_path_element('pdfannotator_report', '/activity/pdfannotator/annotations/annotation/comments/comment/reports/report');    

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_pdfannotator($data) {
        
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('pdfannotator', $data); // insert the pdfannotator record
        
        $this->apply_activity_instance($newitemid); // immediately after inserting "activity" record, call this
    }
    
    protected function process_pdfannotator_annotation($data) {
        
       global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
 
        $data->pdfannotatorid = $this->get_new_parentid('pdfannotator');
        $data->userid = $this->get_mappingid('user', $data->userid);
 
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        
        $newitemid = $DB->insert_record('pdfannotator_annotationsneu', $data);
        $this->set_mapping('pdfannotator_annotation', $oldid, $newitemid);
        
    }
    
    protected function process_pdfannotator_comment($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
 
        $data->annotationid = $this->get_new_parentid('pdfannotator_annotation');
        $data->userid = $this->get_mappingid('user', $data->userid);
        
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
 
        $newitemid = $DB->insert_record('pdfannotator_comments', $data);
        $this->set_mapping('pdfannotator_comment', $oldid, $newitemid);
    }
    
    protected function process_pdfannotator_report($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
                
        $data->courseid = $this->get_courseid();
                
        $data->commentid = $this->get_new_parentid('pdfannotator_comment');
        $data->userid = $this->get_mappingid('user', $data->userid);
        
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->pdfannotatorid = $this->get_mappingid('pdfannotator', $data->pdfannotatorid); // params: 1. Object class as defined in structure, 2. attribute&/column name
        
        $newitemid = $DB->insert_record('pdfannotator_reports', $data);
        $this->set_mapping('pdfannotator_report', $oldid, $newitemid);
    }
    
//    protected function process_pdfannotator_comment_archiv($data) {
//        global $DB;
// 
//        $data = (object)$data;
//        $oldid = $data->id;
// 
//        $data->annotationid = $this->get_new_parentid('pdfannotator_annotation');
//        $data->userid = $this->get_mappingid('user', $data->userid);
//        
//        $data->timecreated = $this->apply_date_offset($data->timecreated);
//        $data->timemodified = $this->apply_date_offset($data->timemodified);
// 
//        $newitemid = $DB->insert_record('pdfannotator_comments_archiv', $data);
//        $this->set_mapping('pdfannotator_comment_archiv', $oldid, $newitemid);
//    }
//    

    protected function after_execute() {
        // Add pdfannotator related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_pdfannotator', 'intro', null);
        $this->add_related_files('mod_pdfannotator', 'content', null);
    }
}