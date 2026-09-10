import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-whatsapp-no',
  templateUrl: './whatsapp-no.component.html',
  styleUrls: ['./whatsapp-no.component.css']
})
export class WhatsappNoComponent implements OnInit {

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {

    this.getDepartments();
    this.getContacts();
  }

  employees1;
  departments;
  alerts;
  getContacts() {
    this.service.get('common.php?type=getAlertsAndNotifications').subscribe(response => {
      this.alerts = response;
    })
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    })
  }

  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees1 = response;
    });
  }

  isEdit = false;
  selectedResult = [];

  editNumber(data){
    this.selectedResult = data;
    this.isEdit = true;
  }


  isNew = false;

  editSave(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('common.php?type=editAlertAndNotification',JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
         alertify.success('data save Successfuly');
        data.resetForm();
        this.getContacts();
        this.isEdit = false;
      }else{
        alertify.error('Error Occured');
      }
    });
  }


  save(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp=data.value
    this.service.post('common.php?type=saveAlertAndNotification',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
         alertify.success('data save Successfuly');
        data.resetForm();
        this.getContacts();
        this.isNew = false;
      }else{
        alertify.error(response['msg']);
      }
    });
  }





}
