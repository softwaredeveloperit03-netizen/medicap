import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-appraisal-checklist',
  templateUrl: './appraisal-checklist.component.html',
  styleUrls: ['./appraisal-checklist.component.css']
})
export class AppraisalChecklistComponent implements OnInit {
  appList = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  addData(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.appList[this.appList.length] = temp;
    data.resetForm();
  }
  delData(index) {
    this.appList.splice(index, 1);
  }


  save(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp=data.value
    temp['add']=this.appList;
    this.service.post('master/appraisal.php?type=saveAppraisalchecklist',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        // this.router.navigate(['/hr/task']);
        alertify.success('data save Successfuly');
        data.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }

}
