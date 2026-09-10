import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-card',
  templateUrl: './card.component.html',
  styleUrls: ['./card.component.css']
})
export class CardComponent implements OnInit {
  selectedResult=[];
  isView= false;
  results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getLeaves();
  }

getLeaves(){
  this.service.get('hr/leavepolicy.php?type=getLeavePolicy').subscribe(response => {
    this.results = response;
  })
}

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  // saveForm(data){
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.service.post('hr/leavepolicy.php?type=updateLeavePolicy&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success('Record Inserted Successfully');
  //       data.resetForm();
  //       this.isView = false;
  //       this.getLeaves();
       
  //     } else {
  //       alertify.error('Failed: An error occured, Please try again!');
  //     }
  //   });
  // }

  save(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = Form.value;
  temp['firstname']=this.selectedResult['firstname']
  temp['emp_id']=this.selectedResult['emp_id']
  temp['leave_title']=this.selectedResult['leave_title']
  temp['designation_heading']=this.selectedResult['designation_heading']
  temp['Eefective_from']=this.selectedResult['Eefective_from']
  temp['Eefective_to']=this.selectedResult['Eefective_to']
  temp['total_leave']=this.selectedResult['total_leave']
  temp['allow_leave']=this.selectedResult['allow_leave']
  temp['leave_deduct']=this.selectedResult['leave_deduct']
  temp['salary_day']=this.selectedResult['salary_day']
  temp['over_time']=this.selectedResult['over_time']
  temp['leaveList']=this.selectedResult['leaveList']
      this.service.post('hr/leavepolicy.php?type=save_leave_card&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
