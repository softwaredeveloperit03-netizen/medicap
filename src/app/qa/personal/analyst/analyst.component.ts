import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-analyst',
  templateUrl: './analyst.component.html',
  styleUrls: ['./analyst.component.css']
})
export class AnalystComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getDepartments();
  }

  departments;
  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response =>{
      this.departments = response;

    } )
  }

  employees;
  getEmployees(value) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + value).subscribe(response => {
      this.employees = response;
    });
  }
  selectedEmp=[]
  empId;contact_no;emp_email;birthdate;
  selectEmp(index) {
    // index = index - 1;
    // if (index !== -1) {
      this.selectedEmp= this.employees[index-1];
      this.empId= this.selectedEmp['emp_id'];
      this.contact_no= this.selectedEmp['contact_no'];
      this.emp_email= this.selectedEmp['emp_email'];
      this.birthdate= this.selectedEmp['birthdate'];
    //}
  }

  selectedFile2: File;


  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
  
  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile2 !== undefined) {
      uploadData.append('q_document', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qa/all2.php?type=save_analyst', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }


}
