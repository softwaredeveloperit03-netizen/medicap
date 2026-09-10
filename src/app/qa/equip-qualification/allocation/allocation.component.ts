import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

 
  isNew = false;
  dept;
  results;
  selectedSection=[];
    router: any;
    employees;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getDepartment();
    this.getData();
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.dept=response;
    });
  }

  getData(){
    this.service.get('qa/qualification.php?type=getQualificationRequest').subscribe(response=>{
      this.results=response;
    });
  }

  selectedEmp =[];
  selectEmployee(index){
    this.selectedEmp =  this.employees[index-1];
  }

  getemployeeByDept(value){
    this.service.get('qa/qualification.php?type=getemployeeByDept&deptName='+value).subscribe(response=>{
      this.employees=response;
    });
  } 

  saveRequest(data){
    this.service.post('qa/qualification.php?type=saveQualificationRequest',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Save data successfuly');
        data.resetForm();
        this.getData();
        this.isNew = false;
        
      }else("Some Error Occured");
    });
  }



}
