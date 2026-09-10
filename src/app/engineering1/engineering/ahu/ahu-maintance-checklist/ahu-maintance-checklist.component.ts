import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ahu-maintance-checklist',
  templateUrl: './ahu-maintance-checklist.component.html',
  styleUrls: ['./ahu-maintance-checklist.component.css'],
})
export class AhuMaintanceChecklistComponent implements OnInit {
  isNew = false;
  isView = false;
  selectedDays: any = '';
  selectedAhu = '';
  monthlyList = [];
  quarterlyList = [];
  annualList = [];
  result = [];
  loading: any;
  Ahu: any[] = [];
  selectedResult: any = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    this.getAhuMaintanceList();
    this.getAhuList();
  }
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
          this.loggedInDept
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

  getAhuList() {
    this.service
      .get('engineering/ahu.php?type=getAhu')
      .subscribe((response: any) => {
        this.Ahu = Array.isArray(response) ? response : [];
      });
  }

  getAhuMaintanceList() {
    this.service
      .get('engineering/ahu.php?type=getAhuMaintance')
      .subscribe((response) => {
        this.result = Array.isArray(response) ? response : [];
      });
  }

  add(formData: any) {
    const temp = formData.value || {};
    if (!temp.description || !temp.complete) {
      alertify.error('Please enter activity description and complete status');
      return;
    }
    const list = {
      description: temp.description,
      complete: temp.complete,
      comment: temp.comment || '',
    };

    if (this.selectedDays == 'Monthly') {
      this.monthlyList.push(list);
    } else if (this.selectedDays == 'Quarterly') {
      this.quarterlyList.push(list);
    } else if (this.selectedDays == 'Annual') {
      this.annualList.push(list);
    } else {
      alertify.error('Please select Maintenance To Be Performed');
      return;
    }

    // Clear only activity row fields
    formData.controls.description && formData.controls.description.reset();
    formData.controls.complete && formData.controls.complete.reset('Yes');
    formData.controls.comment && formData.controls.comment.reset();
  }

  del(index: number) {
    if (this.selectedDays == 'Monthly') {
      this.monthlyList.splice(index, 1);
    } else if (this.selectedDays == 'Quarterly') {
      this.quarterlyList.splice(index, 1);
    } else if (this.selectedDays == 'Annual') {
      this.annualList.splice(index, 1);
    }
  }

  openNew() {
    this.isNew = true;
    this.selectedDays = '';
    this.selectedAhu = '';
    this.monthlyList = [];
    this.quarterlyList = [];
    this.annualList = [];
  }

  save(data) {
    if (!this.selectedAhu) {
      alertify.error('Please select AHU');
      return;
    }
    if (!this.selectedDays) {
      alertify.error('Please select Maintenance To Be Performed');
      return;
    }

    const activityCount =
      this.selectedDays == 'Monthly'
        ? this.monthlyList.length
        : this.selectedDays == 'Quarterly'
        ? this.quarterlyList.length
        : this.annualList.length;

    if (!activityCount) {
      alertify.error('Please add at least one maintenance activity');
      return;
    }

    const formVal = data.value || {};
    if (!formVal.completed_by || !formVal.completed_date || !formVal.reviewed_by || !formVal.reviewed_date) {
      alertify.error('Please fill Work Completed and Reviewed By details');
      return;
    }

    const temp: any = {
      Ahu: this.selectedAhu,
      Maintance: this.selectedDays,
      remark: formVal.remark || '',
      completed_by: formVal.completed_by,
      completed_date: formVal.completed_date,
      reviewed_by: formVal.reviewed_by,
      reviewed_date: formVal.reviewed_date,
      monthly: this.selectedDays == 'Monthly' ? this.monthlyList : [],
      quarterly: this.selectedDays == 'Quarterly' ? this.quarterlyList : [],
      annual: this.selectedDays == 'Annual' ? this.annualList : [],
    };

    this.service
      .post('engineering/ahu.php?type=saveAhuMaintance', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Records Save Successfully');
          this.isNew = false;
          this.monthlyList = [];
          this.quarterlyList = [];
          this.annualList = [];
          this.selectedDays = '';
          this.selectedAhu = '';
          this.getAhuMaintanceList();
        } else {
          alertify.error('Error to Save Records !! ' + (response['status'] || ''));
        }
      });
  }

  View(index: number) {
    this.selectedResult = this.result[index];
    this.isView = true;
  }
}
