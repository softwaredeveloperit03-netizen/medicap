import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-outpass-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class OutpassNewComponent implements OnInit {

  emp_id = '';
  emp_name = '';
  department = '';
  reason = '';
  reason_details = '';
  reasons = ['Official Duty', 'Personal', 'Medical', 'Bank/Government Work', 'Other'];

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.emp_id = localStorage.getItem('emp_id') || '';
    this.emp_name = localStorage.getItem('username') || '';
    this.department = localStorage.getItem('department') || '';
  }

  saveOutpass(form: any) {
    if (!form.valid) {
      alertify.error('All required fields must be filled');
      return;
    }
    const payload = {
      emp_id: this.emp_id,
      emp_name: this.emp_name,
      department: this.department,
      reason: this.reason,
      reason_details: this.reason_details
    };
    this.service.post('security/outpass_api.php?type=saveOutpass', JSON.stringify(payload)).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Outpass saved successfully. Pass No: ' + (res.pass_no || ''));
        form.resetForm();
        this.reason = '';
        this.reason_details = '';
      } else {
        alertify.error(res?.message || 'Failed to save');
      }
    });
  }
}
