import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {

  departments;
  emp_name;
  date;
  time;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }


  saveform(Form) {
      if (!Form.valid) {
        alertify.error('All fields are required');
        return;
      } 
      let temp = Form.value;
      this.service.post('security/gatepass.php?type=saveKeyregister', JSON.stringify(temp))
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
