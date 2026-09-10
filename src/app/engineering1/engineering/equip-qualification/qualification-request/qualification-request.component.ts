import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-qualification-request',
  templateUrl: './qualification-request.component.html',
  styleUrls: ['./qualification-request.component.css']
})
export class QualificationRequestComponent implements OnInit {

  isNew = false;
  dept; 
  results;
  selectedSection=[];
  constructor(private service:DataAccessService,  private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
    this.getDepartment();
    this.getData();
     this.get_rights();

  }
  getData() {
   
    this.service.get('qa/qualification.php?type=getQualificationRequest').subscribe(response => {
      this.results = response;
    
    });

  }
  close() {
    this.isNew = false;
    window.location.reload();
    this.router.navigate([
      '/engineering/equip-qualification/qualification-request',
    ]);
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.dept=response;
    });
  }

  getSection(index){
    index=index-1;
    if(index != -1){
      // let AllData=this.dept['sections'];
      // this.selectedSection=AllData[index]
     this.selectedSection=this.dept[index]
    }
  
  }

  // saveRequest(data){
  //   this.service.post('qa/qualification.php?type=saveQualificationRequest',JSON.stringify(data.value)).subscribe(response=>{
  //     if(response['status']=='success'){
  //       alertify.success('Save data successfuly');
  //       data.resetForm();
  //     }else("Some Error Occured");
  //   });
  // }

  saveRequest(data){
    if(!data.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value
    this.service.post('qa/qualification.php?type=saveQualificationRequest',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
         this.router.navigate([
           'engineering/equip-qualification/qualification-request',
         ]);
        window.location.reload();
        alertify.success('data save Successfuly');
        data.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
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


}
