import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  result;
  employeeList=[];
  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit() {
    this.getEmployees();
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }
  save(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp=data.value

    this.service.post('hr/task.php?type=saveTask',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/hr/task']);
        alertify.success('data save Successfuly');
        data.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }

}
