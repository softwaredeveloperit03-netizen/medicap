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
  checkList = [];
  departments;
  designations;
  result;
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    // this.getDesignations();
  }


getDepartments(){
  this.service.get('common.php?type=getDepartments').subscribe(response => {
    this.departments = response;
  })
}
department;
getDesignations(value){
  this.service.get('common.php?type=get_Designationss&department1='+this.department).subscribe(response => {
    this.designations = response;
  })
}

  addData(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    // temp['result']=this.result;
    this.checkList[this.checkList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.checkList.splice(index, 1);
  }

  saveCheckPoint(data) {
     
    if (!data.valid) {
      alertify.error('All fields are required')
    }

    let temp = data.value;
    temp['checkList'] = this.checkList;
    this.service.post('master/checklist.php?type=SaveCheckList', JSON.stringify(temp)).subscribe(response => {
      if (response['status']=== 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(["/master/hra/checklist-master"])
      } else {
        alert('Please try Again');
      }
    })
    

  }
  
}