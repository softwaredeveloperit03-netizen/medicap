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
 
  departments;
  employees;

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
 
  getDesignation(data) {
    let department = data.value;
  }

  
  getEmployees(value) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + value).subscribe(response => {
      this.employees = response;
      console.log('this.employees',this.employees);
    });
  }

  selectedemp;
  trainer_name = '';
  designation = '';
  qualification = '';
  contact_no = '';
  email = '';
  experience = '';


  getempdetails(index){
    index = index-1;

    this.selectedemp = this.employees[index];

    this.trainer_name = this.selectedemp['firstname']+' '+this.selectedemp['lastname'];
    this.designation = this.selectedemp['designation'];
     this.contact_no = this.selectedemp['permanent_mobile_no'];
    this.email = this.selectedemp['emp_email'];
 

  }





  skills_data =[];

  addSkilss(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.skills_data.push(temp);
    data.reset();
  }

  delSkills(index){
      this.skills_data.splice(index,1);
  }

  trainer_type ='';


  certificate:File;

  onFileChanged(event) {
    if (event.target.files.length === 1) {
          this.certificate = event.target.files[0];
      }
  }
  


  

  save(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const uploadData = new FormData();

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
  
    
    if (this.certificate !== undefined) {
      uploadData.append('certificate', this.certificate, this.certificate.name);
    }

    uploadData.append('skills_data', JSON.stringify(this.skills_data));


    this.service.post('training.php?type=saveExternalTrainer', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        data.reset();
        this.skills_data =[];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

   

}
