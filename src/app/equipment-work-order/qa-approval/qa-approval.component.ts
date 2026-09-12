import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-equipment-work-order-qa-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['../work-order-form.theme.css'],
})
export class QaApprovalComponent implements OnInit {
  department = '';
  results: any[] = [];
  selected: any = null;
  isView = false;
  today = new Date().toISOString().substring(0, 10);
  form: any = { qa_reinstate: '', qa_approved_by: '', qa_approved_date: '' };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.form.qa_approved_by =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.form.qa_approved_date = this.today;
    this.load();
  }

  load(): void {
    this.service
      .get('engineering/equipment_work_order.php?type=getByStatus&status=TO_QA&scope=all')
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
      alertify.error('Please complete QA approval fields');
      return;
    }
    this.service
      .post(
        'engineering/equipment_work_order.php?type=saveQaApproval&id=' + this.selected.id,
        JSON.stringify(this.form)
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('QA Approval saved — Work Order Closed');
          this.isView = false;
          this.load();
        } else {
          alertify.error('Failed: ' + (response?.status || 'error'));
        }
      });
  }
}
