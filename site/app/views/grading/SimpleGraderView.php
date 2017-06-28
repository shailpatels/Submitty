<?php

namespace app\views\grading;

use app\models\Gradeable;
use app\models\User;
use app\views\AbstractView;

class SimpleGraderView extends AbstractView {

    /**
     * @param Gradeable $gradeable
     * @param Gradeable[] $rows
     * @param array       $graders
     *
     * @return string
     */
    public function simpleDisplay($gradeable, $rows, $graders) {
        $action = ($gradeable->getType() === 1) ? 'lab' : 'numeric';
        $return = <<<HTML
<div class="content">
    <div style="float: right; margin-bottom: 10px; margin-left: 20px">
HTML;

        // Default is viewing your sections sorted by id
        // Limited grader does not have "View All"
        // If nothing to grade, Instuctor will see all sections
        if(!isset($_GET['sort'])){
            $sort = 'id';
        }
        else{
            $sort = $_GET['sort'];
        }
        if (!isset($_GET['view']) || $_GET['view'] !== 'all') {
            $text = 'View All';
            $view = 'all';
        }
        else{
            $text = 'View Your Sections';
            $view = null;
        }
        if($gradeable->isGradeByRegistration()){
            $grading_count = count($this->core->getUser()->getGradingRegistrationSections());
        }
        else{
            $grading_count = count($this->core->getQueries()->getRotatingSectionsForGradeableAndUser($gradeable->getId(),$this->core->getUser()->getId()));
        }

        if($this->core->getUser()->accessFullGrading() && (!$this->core->getUser()->accessAdmin() || $grading_count !== 0)){
            $return .= <<<HTML
        <a class="btn btn-default"
            href="{$this->core->buildUrl(array('component' => 'grading', 'page' => 'simple', 'action' => $action, 'g_id' => $gradeable->getId(), 'sort' => $sort, 'view' => $view))}">$text</a>
HTML;
        }

        $return .= <<<HTML
    </div>
HTML;


        if(isset($_GET['view']) && $_GET['view'] == 'all'){
            $view = 'all';
        }
        else{
            $view = null;
        }

        if($action == 'lab'){
            $info = "No Color - No Credit<br />
                    Dark Blue - Full Credit<br />
                    Light Blue - Half Credit<br />
                    Red - [SAVE ERROR] Refresh Page";
        }
        else{
            $info = "Red - [SAVE ERROR] Refresh Page";
        }
            $return .= <<<HTML
    <i class="fa fa-question-circle tooltip" style="float: right" aria-hidden="true"><span class="tooltiptext">$info</span></i>
HTML;

        $return .= <<<HTML
    <h2>{$gradeable->getName()}</h2>
    <table class="table table-striped table-bordered persist-area">
        <thead class="persist-thead">
            <tr>
                <td width="1%"></td>
                <td width="3%">Section</td>
                <td width="68" style="text-align: left"><a href="{$this->core->buildUrl(array('component' => 'grading', 'page' => 'simple', 'action' => $action, 'g_id' => $gradeable->getId(), 'sort' => 'id', 'view' => $view))}"><span class="tooltiptext" title="sort by ID" aria-hidden="true">User ID </span><i class="fa fa-sort"></i></a></td>
                <td width="92" style="text-align: left"> <a href="{$this->core->buildUrl(array('component' => 'grading', 'page' => 'simple', 'action' => $action, 'g_id' => $gradeable->getId(), 'sort' => 'first', 'view' => $view))}"><span class="tooltiptext" title="sort by First Name" aria-hidden="true">First Name </span><i class="fa fa-sort"></i></a></td>
                <td width="91" style="text-align: left"> <a href="{$this->core->buildUrl(array('component' => 'grading', 'page' => 'simple', 'action' => $action, 'g_id' => $gradeable->getId(), 'sort' => 'last', 'view' => $view))}"><span class="tooltiptext" title="sort by Last Name" aria-hidden="true">Last Name </span><i class="fa fa-sort"></i></a></td>
HTML;
        if($action == 'lab'){
            foreach ($gradeable->getComponents() as $component) {
                $return .= <<<HTML
                <td width="100">{$component->getTitle()}</td>
HTML;
            }
        }
        else{
            $num_text = 0;
            $num_numeric = 0;
            foreach ($gradeable->getComponents() as $component) {
                if($component->getIsText()){
                    $num_text++;
                }
                else{
                    $num_numeric++;
                }
            }
            if($num_numeric !== 0){
                foreach ($gradeable->getComponents() as $component) {
                    if(!$component->getIsText()){
                        $return .= <<<HTML
                <td width="35" style="text-align: center">{$component->getTitle()}({$component->getMaxValue()})</td>
HTML;
                    }
                }
                $return .= <<<HTML
                <td width="25" style="text-align: center">Total</td>
HTML;
            }
            foreach ($gradeable->getComponents() as $component) {
                if($component->getIsText()){
                    $return .= <<<HTML
                <td style="text-align: center">{$component->getTitle()}</td>
HTML;
                }
            }
        }

        $return .= <<<HTML
            </tr>
        </thead>
        <tbody>
HTML;

        $count = 1;
        $row = 0;
        $last_section = false;
        $tbody_open = false;
        $colspan = 5 + count($gradeable->getComponents());
        if($action == 'numeric'){
            $colspan++;
        }
        if(count($rows) == 0){
            $return .= <<<HTML
            <tr class="info">
                <td colspan="{$colspan}" style="text-align: center">No Grading To Be Done! :)</td>
            </tr>
HTML;
        }
        foreach ($rows as $gradeable_row) {
            if ($gradeable->isGradeByRegistration()) {
                $section = $gradeable_row->getUser()->getRegistrationSection();
            }
            else {
                $section = $gradeable_row->getUser()->getRotatingSection();
            }
            $display_section = ($section === null) ? "NULL" : $section;
            if ($section !== $last_section) {
                $last_section = $section;
                $count = 1;
                if ($tbody_open) {
                    $return .= <<<HTML
        </tbody>
HTML;
                }
                if (isset($graders[$display_section]) && count($graders[$display_section]) > 0) {
                    $section_graders = implode(", ", array_map(function(User $user) { return $user->getId(); }, $graders[$display_section]));
                }
                else {
                    $section_graders = "Nobody";
                }
                $return .= <<<HTML
            <tr class="info persist-header">
                <td colspan="{$colspan}" style="text-align: center">
                Students Enrolled in Section {$display_section}
HTML;
                if($action == 'lab'){
                    $return .= <<<HTML
                    <a target=_blank href="{$this->core->getConfig()->getTaBaseUrl()}/account/print/print_checkpoints_gradeable.php?course={$this->core->getConfig()->getCourse()}&semester={$this->core->getConfig()->getSemester()}&g_id={$gradeable->getId()}&section_id={$display_section}&grade_by_reg_section={$gradeable->isGradeByRegistration()}&sort_by={$sort}">
                        <i class="fa fa-print"></i>
                    </a>
HTML;
                }
                $return .= <<<HTML
                </td>
            </tr>
            <tr class="info">
                <td colspan="{$colspan}" style="text-align: center">Graders: {$section_graders}</td>
            </tr>
        <tbody id="section-{$section}">
HTML;
            }
            $return .= <<<HTML
            <tr data-gradeable="{$gradeable->getId()}" data-user="{$gradeable_row->getUser()->getId()}">
                <td class="">{$count}</td>
                <td class="">{$gradeable_row->getUser()->getRegistrationSection()}</td>
                <td class="cell-all" style="text-align: left">{$gradeable_row->getUser()->getId()}</td>
                <td class="" style="text-align: left">{$gradeable_row->getUser()->getDisplayedFirstName()}</td>
                <td class="" style="text-align: left">{$gradeable_row->getUser()->getLastName()}</td>
HTML;

            if($action == 'lab'){
                $col = 0;
                foreach ($gradeable_row->getComponents() as $component) {
                    if ($component->getIsText()) {
                        $return .= <<<HTML
                <td>{$component->getComment()}</td>
HTML;
                    }
                    else {
                        if($component->getScore() === 1.0) {
                            $background_color = "background-color: #149bdf";
                        }
                        else if($component->getScore() === 0.5) {
                            $background_color = "background-color: #88d0f4";
                        }
                        else {
                            $background_color = "";
                        }
                        $return .= <<<HTML
                <td class="cell-grade" id="cell-{$row}-{$col}" data-id="{$component->getId()}" data-score="{$component->getScore()}" style="{$background_color}"></td>
HTML;
                    }
                    $gradeable_row++;
                    $col++;
                }
            }
            else{
                $col = 0;
                $total = 0;
                if($num_numeric !== 0){
                    foreach ($gradeable_row->getComponents() as $component) {
                        if (!$component->getIsText()) {
                            $total+=$component->getScore();
                            if($component->getScore() == 0){
                                $return .= <<<HTML
                <td class="option-small-input"><input class="option-small-box" style="text-align: center; color: #bbbbbb;" type="text" id="cell-{$row}-{$col}" value="{$component->getScore()}" data-id="{$component->getId()}" data-num="true"/></td>
HTML;
                            }
                            else{
                                $return .= <<<HTML
                <td class="option-small-input"><input class="option-small-box" style="text-align: center" type="text" id="cell-{$row}-{$col}" value="{$component->getScore()}" data-id="{$component->getId()}" data-num="true"/></td>
HTML;
                            }
                            $gradeable_row++;
                            $col++;
                        }
                    }
                    $return .= <<<HTML
                <td class="option-small-output" value="toobadthiswontprint"><input class="option-small-box" style="text-align: center" type="text" border="none" value=$total data-total="true" readonly></td>
HTML;
                }

                foreach ($gradeable_row->getComponents() as $component) {
                    if ($component->getIsText()) {
                        $return .= <<<HTML
                <td class="option-small-input"><input class="option-small-box" type="text" id="cell-{$row}-{$col}" value="{$component->getComment()}" data-id="{$component->getId()}"/></td>
HTML;
                        $gradeable_row++;
                        $col++;
                    }
                }
            }
            $return .= <<<HTML
            </tr>
HTML;
            $row++;
            $count++;
        }

        $return .= <<<HTML
        </tbody>
    </table>
</div>
HTML;

        return $return;
    }
}