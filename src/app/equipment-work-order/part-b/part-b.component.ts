import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  INTERIOR_CHECKPOINTS,
  EXTERIOR_CHECKPOINTS,
  FacilityChecklistItem,
  buildChecklistItems,
} from '../checklist/facility-checklist.items';
declare let alertify: any;

@Component({
  selector: 'app-equipment-work-order-part-b',
  templateUrl: './part-b.component.html',
  styleUrls: ['../work-order-form.theme.css'],
})
export class PartBComponent implements OnInit {
  results: any[] = [];
  selected: any = null;
  isView = false;
  today = new Date().toISOString().substring(0, 10);

  /** Area only: Interior | Exterior */
  checklistType = '';
  checklistItems: FacilityChecklistItem[] = [];
  checklistMeta: any = {
    area_department: '',
    checklist_date: '',
    performed_by: '',
    performed_date: '',
    action_taken_by: '',
    action_taken_date: '',
    admin_dept_head: '',
    admin_dept_head_date: '',
    verified_by: '',
    verified_date: '',
    qa_by: '',
    qa_date: '',
  };

  form: any = {
    work_performed: '',
    parts_used: '',
    require_requalification: '',
    require_calibration: '',
    requal_calib_date: '',
    performed_by: '',
    performed_date: '',
  };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.form.performed_by =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.form.performed_date = this.today;
    this.load();
  }

  get isArea(): boolean {
    return String(this.selected?.asset_type || '').toLowerCase() === 'area';
  }

  get checklistFormNo(): string {
    return this.checklistType === 'Exterior' ? 'FEN-001-01-D' : 'FEN-001-01-C';
  }

  get checklistTitle(): string {
    return this.checklistType === 'Exterior'
      ? 'Facility Exterior Maintenance Monthly Checklist'
      : 'Facility Interior Maintenance Monthly Checklist';
  }

  load(): void {
    this.service
      .get('engineering/equipment_work_order.php?type=getByStatus&status=TO_PART_B&scope=all')
      .subscribe((response) => {
        this.results = (response as any[]) || [];
      });
  }

  view(item: any): void {
    this.selected = item;
    this.checklistType = '';
    this.checklistItems = [];
    const name =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.checklistMeta = {
      area_department: [item.initiating_department, item.equipment_name].filter(Boolean).join(' / '),
      checklist_date: this.today,
      performed_by: name,
      performed_date: this.today,
      action_taken_by: '',
      action_taken_date: this.today,
      admin_dept_head: '',
      admin_dept_head_date: '',
      verified_by: '',
      verified_date: '',
      qa_by: '',
      qa_date: '',
    };
    this.form = {
      work_performed: '',
      parts_used: '',
      require_requalification: '',
      require_calibration: '',
      requal_calib_date: '',
      performed_by: name,
      performed_date: this.today,
    };
    this.isView = true;
  }

  onChecklistTypeChange(): void {
    if (this.checklistType === 'Interior') {
      this.checklistItems = buildChecklistItems(INTERIOR_CHECKPOINTS);
    } else if (this.checklistType === 'Exterior') {
      this.checklistItems = buildChecklistItems(EXTERIOR_CHECKPOINTS);
    } else {
      this.checklistItems = [];
    }
  }

  save(formRef: any): void {
    if (!formRef.valid) {
      alertify.error('Please complete Work Performed fields');
      return;
    }
    if (this.isArea) {
      if (!this.checklistType) {
        alertify.error('Select Facility Interior or Exterior checklist');
        return;
      }
      if (!this.checklistMeta.checklist_date || !this.checklistMeta.performed_by) {
        alertify.error('Please fill checklist Date and Performed by');
        return;
      }
    }

    const payload: any = { ...this.form };
    if (this.isArea) {
      payload.checklist_type = this.checklistType;
      payload.checklist = {
        ...this.checklistMeta,
        items: this.checklistItems,
      };
    }

    this.service
      .post(
        'engineering/equipment_work_order.php?type=savePartB&id=' + this.selected.id,
        JSON.stringify(payload)
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Work performed saved — sent for verification');
          this.isView = false;
          this.load();
        } else {
          alertify.error('Failed: ' + (response?.error || response?.status || 'error'));
        }
      });
  }
}
