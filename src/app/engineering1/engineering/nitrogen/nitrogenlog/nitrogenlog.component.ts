import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 

@Component({
  selector: 'app-nitrogenlog',
  templateUrl: './nitrogenlog.component.html',
  styleUrls: ['./nitrogenlog.component.css']
})
export class NitrogenlogComponent implements OnInit {
  isView=false
  isNew=false
  result:any
  resultList=[]
  selectedResult=[]
  constructor(private service : DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getNitrogenLog();
    this.get_rights();
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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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

  getNitrogenLog(){
    this.service.get('engineering/nitrogen.php?type=getNitrogenLog').subscribe(response =>{
      this. result=response;


    });
  }

  save(data:any)
  {
    let temp=data.value

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
   
    this.service.post('engineering/nitrogen.php?type=saveNitrogenLog',JSON.stringify (temp)).subscribe(response =>{
      if (response['status'] === 'success') {
        // console.log(this.result)
        // this.getHdpe();
        this.getNitrogenLog()
        alertify.success('Record Inserted successfully');
        data.resetForm();
        this.isNew=false;
        this.getNitrogenLog()
      } else {
        alertify.error(response['status']);
      }
    });
  }

  View(index)
  {
    this.selectedResult=this.result[index]
    this.isView=true;


  }



}
