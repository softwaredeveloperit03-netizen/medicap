import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  buildGroupedChecklistList,
  getEvaluationParameterName,
  GroupedChecklistRow,
} from '../checklist-shared';

declare let alertify;

interface ChecklistCheckpoint {
  check_point: string;
  evaluation_parameter: string;
  description?: string;
}

interface ChecklistSection {
  heading: string;
  checkpoints: ChecklistCheckpoint[];
}

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css', '../checklist-table.shared.css'],
})
export class NewComponent implements OnInit {
  /** Logged-in user's department — controls Close link and which checklist dept options are available */
  loggedInDepartment = '';
  /** Form selection: QC, Store, or HR */
  department = '';
  module: string;
  form_name: string;
  sectionHeading = '';
  evaluation_parameter: string;
  sections: ChecklistSection[] = [];
  checkListData: any[] = [];
  groupedSavedList: GroupedChecklistRow[] = [];
  loading = false;

  getEvaluationParameterName = getEvaluationParameterName;

  constructor(
    private service: DataAccessService,
    public route: ActivatedRoute,
    private router: Router
  ) {}

  get isHrUser(): boolean {
    return this.loggedInDepartment === 'Human Resource';
  }

  ngOnInit(): void {
    this.loggedInDepartment = localStorage.getItem('department') || '';
    this.department = '';
    this.getCheckListData();
  }

  getCheckListData(): void {
    this.loading = true;
    this.service.get('master/checklist.php?type=getMastercheckList').subscribe(
      (response: any) => {
        this.checkListData = Array.isArray(response) ? response : [];
        this.groupedSavedList = buildGroupedChecklistList(this.checkListData);
        this.loading = false;
      },
      () => {
        this.checkListData = [];
        this.groupedSavedList = [];
        this.loading = false;
      }
    );
  }

  private findSectionIndex(heading: string): number {
    const normalized = heading.trim().toLowerCase();
    return this.sections.findIndex(
      (s) => s.heading.trim().toLowerCase() === normalized
    );
  }

  addCheckpoint(checkForm: any): void {
    if (!checkForm.valid) {
      alertify.error('Checkpoint Particulars and Evaluation Parameter are required');
      return;
    }
    const heading = (this.sectionHeading || '').trim();
    if (!heading) {
      alertify.error('Heading For Checklist is required');
      return;
    }

    const checkpoint: ChecklistCheckpoint = {
      check_point: checkForm.value.check_point,
      evaluation_parameter: checkForm.value.evaluation_parameter,
      description: ' ',
    };

    const sectionIndex = this.findSectionIndex(heading);
    if (sectionIndex >= 0) {
      this.sections[sectionIndex].checkpoints.push(checkpoint);
    } else {
      this.sections.push({ heading, checkpoints: [checkpoint] });
    }

    checkForm.resetForm();
    this.evaluation_parameter = '';
  }

  removeCheckpoint(sectionIndex: number, checkpointIndex: number): void {
    this.sections[sectionIndex].checkpoints.splice(checkpointIndex, 1);
    if (!this.sections[sectionIndex].checkpoints.length) {
      this.sections.splice(sectionIndex, 1);
    }
  }

  removeSection(sectionIndex: number): void {
    this.sections.splice(sectionIndex, 1);
  }

  getTotalCheckpoints(): number {
    return this.sections.reduce((sum, s) => sum + s.checkpoints.length, 0);
  }

  saveChecklist(checklistForm: any): void {
    if (!checklistForm.valid) {
      alertify.error('Department, Module and Form Name are required');
      return;
    }
    if (!this.sections.length || !this.getTotalCheckpoints()) {
      alertify.error('Add at least one checklist section with checkpoints');
      return;
    }

    const payload = {
      department: checklistForm.value.department,
      module: checklistForm.value.module,
      form_name: checklistForm.value.form_name,
      sections: this.sections,
    };

    this.service
      .post('master/checklist.php?type=SaveMastercheckList', JSON.stringify(payload))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Checklist saved successfully');
          this.sections = [];
          this.sectionHeading = '';
          checklistForm.resetForm();
          this.department = '';
          this.module = '';
          this.form_name = '';
          this.getCheckListData();
        } else {
          alertify.error('Failed: ' + (response['status'] || 'Please try again'));
        }
      });
  }
}
