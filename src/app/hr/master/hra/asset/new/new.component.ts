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

  constructor(private service:DataAccessService, private router: Router) { }

  departments;
  list = [];
  ngOnInit() {
    this.getDepartment();
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    })
  }
  
  savemaster(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/asset.php?type=saveAsset_issued',JSON.stringify(Form.value)).subscribe(response=>{
      if(response['status']==='success'){
        this.router.navigate(['/hr/asset']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }
}
