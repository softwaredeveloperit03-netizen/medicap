import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'Initiaton Of CC', route: 'new', icon: 'fa-plus-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'consernandreview', title: 'CC IMPACT ASSESSMENT', route: 'consernAndReview', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'deptconandrev', title: 'Dept Consent & Review', route: 'deptConAndRev', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'assessmentbyqa', title: 'QA Manager', route: 'assessmentByQa', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'actionandreviewbyqa', title: 'Reviewed By QA', route: 'actionAndReviewByQA', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'impactregulatory', title: 'Impact On Regulatory Affairs And', route: 'impactRegulatory', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'qaassementchecklist', title: 'QA Assessment Checklist', route: 'qaAssementChecklist', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'closinchecklist', title: 'Closin Checklist', route: 'closinChecklist', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
  ];

  constructor(private service: DataAccessService) {
    this.Department = localStorage.getItem('department');
  }
  Department = localStorage.getItem('department');

  ccSearch = '';
  travelStages: { key: string; label: string; completed: boolean; date: string | null; user: string | null }[] = [];
  travelMessage = '';
  travelProgressPercent = 0;

  ngOnInit(): void {
    this.get_rights();
    this.Department = localStorage.getItem('department');
  }

  loadTravelHistory(): void {
    const ctrl = (this.ccSearch || '').trim();
    if (!ctrl) {
      this.travelMessage = 'Enter a CC number and press Search.';
      this.travelStages = [];
      this.travelProgressPercent = 0;
      return;
    }
    this.travelMessage = '';
    const plantId = localStorage.getItem('plant_id') || '';
    this.service
      .get('changecontrol1.php?type=getCCTravelHistory&ctrl_no=' + encodeURIComponent(ctrl) + '&plant_id=' + encodeURIComponent(plantId))
      .subscribe((res: any) => {
        if (res && res.found && res.stages && res.stages.length) {
          this.travelStages = res.stages;
          const done = res.stages.filter((s: any) => s.completed).length;
          this.travelProgressPercent = (done / res.stages.length) * 100;
          this.travelMessage = 'CC ' + (res.ctrl_no || ctrl) + ' · ' + (res.department_name || '') + ' · ' + (res.status || '');
        } else {
          this.travelStages = [];
          this.travelProgressPercent = 0;
          this.travelMessage = (res && res.message) ? res.message : 'CC not found.';
        }
      }, () => {
        this.travelStages = [];
        this.travelProgressPercent = 0;
        this.travelMessage = 'Error loading history.';
      });
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
}
