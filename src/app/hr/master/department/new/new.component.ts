import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService, private router :Router) {}
  departments;
  ngOnInit() {
    this.getDepartment();
  }
  getDepartment(){
    this.service.get('hr/department.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    })
  }
  saveDepartment(data) {
    if(!data.valid){
      alertify.error('All feilds are Required');
    }
     this.service.post('hr/department.php?type=saveDepartment',JSON.stringify(data.value)).subscribe(response=>{
       if(response['status']=='success'){
          data.resetForm();
          this.router.navigate(['/department']);
          alertify.success('Record Inserted Successfuly');
       }else{
         alertify.error('Failed! some error occured')
       }
     })
  }

}
