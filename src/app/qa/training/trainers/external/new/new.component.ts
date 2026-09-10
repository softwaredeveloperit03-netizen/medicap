import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  designations;
  Qualifications;
  certificate: File;
  resume: File;
  departments;
  employees;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    // this.getDesignations();
    this.getQualifications();
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
 
  getDesignation(data) {
    let department = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }
  getEmployees(value) {
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  getQualifications() {
    this.service.get('common.php?type=getQualifications')
      .subscribe(response => {
        this.Qualifications = response;
      });
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const formData = new FormData();

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      // Use `key` and `value`
      formData.append(key, value);
    }

    if (this.certificate !== undefined) {
      formData.append('certificate', this.certificate, this.certificate.name);
    } else {
      alert('Certificate Required');
      return;
    }
    if (this.resume !== undefined) {
      formData.append('resume', this.resume, this.resume.name);
    } else {
      alert('Resume Required');
      return;
    }

    formData.append('trainer_type', 'external');


    this.service.post('training.php?type=saveExternalTrainer', formData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        data.resetForm();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  onFileChanged1(event) {
    if (event.target.files.length !== 0) {
      this.certificate = event.target.files[0];
    }
  }

  onFileChanged2(event) {
    if (event.target.files.length !== 0) {
      this.resume = event.target.files[0];
    }
  }

}
