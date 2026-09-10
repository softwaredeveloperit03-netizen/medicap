import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-delay',
  templateUrl: './delay.component.html',
  styleUrls: ['./delay.component.css'],
})
export class DelayComponent implements OnInit {
  /** Deviation/Incident number (optional from route for pre-fill) */
  deviationNo = '';
  department = '';
  initialTargetCompletionDate = '';
  dateForJustification = '';
  titleForJustification = '';
  newTargetDateClosure = '';
  justificationText = '';
  initiatedBySignDate = '';
  commentsByManagerQA = '';
  departmentHeadSignDate = '';
  managerQASignDate = '';
  reviewerSignDate = '';
  approverSignDate = '';

  departments: any[] = [];
  deviations: any[] = [];
  selectedDeviationId: number | null = null;
  /** When set, form opens for this deviation (index-wise from list). */
  selectedDeviation: any = null;
  showForm = false;
  saving = false;
  plant_id = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.plant_id = localStorage.getItem('plant_id') || '';
    this.route.queryParams.subscribe((q) => {
      if (q['deviation_no']) this.deviationNo = q['deviation_no'];
      if (q['id']) this.openFormByDeviationId(q['id']);
    });
    this.loadDepartments();
    this.loadDeviationsForDelay();
  }

  loadDepartments(): void {
    this.service.get('hr/payroll.php?type=getDepartments&plant_id=' + this.plant_id).subscribe(
      (res: any) => { this.departments = Array.isArray(res) ? res : []; },
      () => { this.departments = []; }
    );
  }

  /** Load deviations where closure date has passed (for dropdown/link). */
  loadDeviationsForDelay(): void {
    this.service.get('deviation.php?type=getDeviationsPastClosure&plant_id=' + this.plant_id+'&dept=' + localStorage.getItem('department')).subscribe(
      (res: any) => { this.deviations = Array.isArray(res) ? res : []; },
      () => { this.deviations = []; }
    );
  }

  /** Open form for the deviation at given index (index-wise). */
  openFormAtIndex(index: number): void {
    const list = this.deviations;
    if (index < 0 || index >= list.length) return;
    const dev = list[index];
    this.selectedDeviation = dev;
    this.selectedDeviationId = dev.id;
    this.deviationNo = dev.deviation_no || '';
    this.department = dev.devOccuredDept || dev.department || '';
    this.initialTargetCompletionDate = dev.closure_date || '';
    this.showForm = true;
  }

  /** Open form by deviation id (e.g. from query param). */
  openFormByDeviationId(id: string | number): void {
    const numId = typeof id === 'string' ? parseInt(id, 10) : id;
    const idx = this.deviations.findIndex((d: any) => d.id === numId);
    if (idx >= 0) this.openFormAtIndex(idx);
  }

  /** Pre-fill form when user selects a deviation from dropdown (when already in form view). */
  onSelectDeviation(id: number | null): void {
    this.selectedDeviationId = id;
    if (id == null) return;
    const dev = this.deviations.find((d: any) => d.id === id);
    if (dev) {
      this.deviationNo = dev.deviation_no || '';
      this.department = dev.devOccuredDept || dev.department || '';
      if (dev.closure_date) this.initialTargetCompletionDate = dev.closure_date;
    }
  }

  /** Back from form to list. */
  backToList(): void {
    this.showForm = false;
    this.selectedDeviation = null;
    this.selectedDeviationId = null;
    this.deviationNo = '';
    this.department = '';
    this.initialTargetCompletionDate = '';
    this.dateForJustification = '';
    this.titleForJustification = '';
    this.newTargetDateClosure = '';
    this.justificationText = '';
    this.initiatedBySignDate = '';
    this.commentsByManagerQA = '';
    this.departmentHeadSignDate = '';
    this.managerQASignDate = '';
    this.reviewerSignDate = '';
    this.approverSignDate = '';
  }

  save(): void {
    if (!this.deviationNo?.trim()) {
      alertify.warning('Deviation No./Incident No. is required.');
      return;
    }
    if (!this.department?.trim()) {
      alertify.warning('Department is required.');
      return;
    }
    if (!this.initialTargetCompletionDate) {
      alertify.warning('Initial Target Completion Date is required.');
      return;
    }
    if (!this.dateForJustification) {
      alertify.warning('Date for Justification is required.');
      return;
    }
    if (!this.titleForJustification?.trim()) {
      alertify.warning('Title for Justification is required.');
      return;
    }
    if (!this.newTargetDateClosure) {
      alertify.warning('New Target date for closure is required.');
      return;
    }
    if (!this.justificationText?.trim()) {
      alertify.warning('Proper Justification is required.');
      return;
    }

    this.saving = true;
    const payload = {
      deviation_no: this.deviationNo.trim(),
      department: this.department.trim(),
      initial_target_completion_date: this.initialTargetCompletionDate,
      date_for_justification: this.dateForJustification,
      title_for_justification: this.titleForJustification.trim(),
      new_target_date_closure: this.newTargetDateClosure,
      justification_text: this.justificationText.trim(),
      initiated_by_sign_date: this.initiatedBySignDate,
      comments_by_manager_qa: this.commentsByManagerQA,
      department_head_sign_date: this.departmentHeadSignDate,
      manager_qa_sign_date: this.managerQASignDate,
      reviewer_sign_date: this.reviewerSignDate,
      approver_sign_date: this.approverSignDate,
      plant_id: this.plant_id,
    };

    this.service.post('deviation.php?type=saveJustificationForDelay', payload).subscribe(
      (res: any) => {
        this.saving = false;
        if (res?.status === 'success') {
          alertify.success(res?.message || 'Justification for delay saved successfully.');
          this.backToList();
          this.loadDeviationsForDelay();
        } else {
          alertify.error(res?.message || 'Save failed.');
        }
      },
      () => {
        this.saving = false;
        alertify.error('Error saving. Please try again.');
      }
    );
  }

  back(): void {
    if (this.showForm) {
      this.backToList();
    } else {
      this.router.navigate(['/qa/qms/deviation']);
    }
  }
}
