import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  designations;
  departments;
  emp_id;
  from_date;
  to_date
  emp_name;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();

  }
 
  no_day:number = 0;
  calculateDays() {
    if (this.from_date && this.to_date) {
      const start = new Date(this.from_date);
      const end = new Date(this.to_date);

      const timeDifference = Math.abs(end.getTime() - start.getTime()) + (24 * 60 * 60 * 1000);
      this.no_day = Math.ceil(timeDifference / (1000 * 60 * 60 * 24));
    } else {
      this.no_day = null;
    }
  }
  selectedEmp=[];
  get_emp_data(index){
    this.selectedEmp=this.results[index-1];
    console.log('this.selectedEmp :>> ', this.selectedEmp);
  }
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe(response => {
      this.departments = response;
    });
  }
  department_name='';
  results;
  getShiftSchedule() {
    this.service.get('hr/shift.php?type=getShiftScheduleLog&department_name='+this.department_name).subscribe(response => {
      this.results = response;
    });
  }

  getDesignation(idx) {
    this.designations =[];
    this.designations = this.departments[idx-1]['designations']
  }

  saveduty(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp = Form.value;
    temp['emp_name']=this.selectedEmp['firstname'] + ' ' + this.selectedEmp['lastname']
    temp['emp_id']=this.selectedEmp['Empolyee_Id']
    this.service.post('admin/housekeeping.php?type=saveOutdoor_duty', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }
}
