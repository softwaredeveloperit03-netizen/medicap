import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-equipment-work-order-verify',
  templateUrl: './verify.component.html',
  styleUrls: ['../work-order-form.theme.css'],
})
export class VerifyComponent implements OnInit {
  department = '';
  results: any[] = [];
  selected: any = null;
  isView = false;
  today = new Date().toISOString().substring(0, 10);
  form: any = { verified_by: '', verified_date: '' };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.form.verified_by =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.form.verified_date = this.today;
    this.load();
  }

  load(): void {
    this.service
      .get(
        'engineering/equipment_work_order.php?type=getByStatus&status=TO_VERIFY&scope=department&department_name=' +
          encodeURIComponent(this.department)
      )
      .subscribe((response) => {
        this.results = (response as any[]) || [];
      });
  }

  view(item: any): void {
    this.selected = item;
    this.isView = true;
  }

  save(formRef: any): void {
    if (!formRef.valid) {
      alertify.error('Verified By and Date are required');
      return;
    }
    const payload = {
      ...this.form,
      qa_approval_required: this.selected.qa_approval_required,
    };
    this.service
      .post(
        'engineering/equipment_work_order.php?type=saveVerify&id=' + this.selected.id,
        JSON.stringify(payload)
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success(
            response.next_status === 'TO_QA'
              ? 'Verified — sent for QA Approval'
              : 'Verified — Work Order Closed'
          );
          this.isView = false;
          this.load();
        } else {
          alertify.error('Failed: ' + (response?.status || 'error'));
        }
      });
  }
}
