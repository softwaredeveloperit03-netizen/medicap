import { Component, OnInit } from '@angular/core';
 import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-designee',
  templateUrl: './designee.component.html',
  styleUrls: ['./designee.component.css']
})
export class DesigneeComponent implements OnInit {

  employees: any;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getEmployees();
  }

  selectedEmp =[];

  onEmpChange(emp_id: any) {
   let obj =  this.employees.find(item=>item.emp_id == emp_id);
    this.selectedEmp = obj;
  }

 
  getEmployees() {
    this.service.get('hr/responsibility.php?type=getFroDesigneeAcceptance&jaduDept='+localStorage.getItem('department')).subscribe(response => {
      this.employees = response;
     })
  } 

 

  selectedResult =[];
  isView = false;

  View(i){
    this.selectedResult = this.employees[i];
    this.isView = true;
  }


  onSubmit() {

   
    let temp = {};
      this.service.post('hr/responsibility.php?type=AcceptByDesignee&emp_code='+ this.selectedResult['emp_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
         this.isView = false;
        this.getEmployees();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  

}
