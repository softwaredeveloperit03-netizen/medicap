import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 


@Component({
  selector: 'app-eurotherm',
  templateUrl: './eurotherm.component.html',
  styleUrls: ['./eurotherm.component.css']
})
export class EurothermComponent implements OnInit {
isNew=false;
isView=false
result:any
checkpoint:[]
selectedRecord:any
loading: any;
selectedResult=[]
  constructor(private service : DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
    this.getEurothermLog();
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


  getEurothermLog(){
    this.service.get('engineering/Eurotherm.php?type=getEurothermDatabase&record='+this.selectedRecord).subscribe(response =>{
      this.result=response;


    });
  }

  save(data)
  {
     if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
     temp['checkpoint']= this.checkpoint;
     
    this.service.post('engineering/Eurotherm.php?type=saveEurothermDatabase',JSON.stringify (temp)).subscribe(response =>{
      if (response['status'] === 'success') {
       
        this.getEurothermLog();
        this.isNew=false;
        data.resetForm();


        alertify.success('Record Inserted successfully');
        data.resetForm();
        
       
      } else {
        alertify.error(response['status']);
      }
    });

  }
 
  View(index)
  {
    this.selectedResult=this.result[index];
    this.isView=true;

  }

}
