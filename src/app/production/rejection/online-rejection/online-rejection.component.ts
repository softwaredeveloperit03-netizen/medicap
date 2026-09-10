import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-online-rejection',
  templateUrl: './online-rejection.component.html'
})
export class OnlineRejectionComponent implements OnInit {
  dataForm;
  isNew = false;
  constructor(private router:Router, private service: DataAccessService) { }

  ngOnInit(): void {
    this.getrejection();
  }
  rejectionlist;
  getrejection(){
    this.service.get('rejection.php?type=getonlinerejection').subscribe(response => {
      this.rejectionlist = response;
    })
  }
  closeform(){
    this.isNew = false;
  }
  saverejection(dataForm){
    if(dataForm.valid){
      this.service.post('rejection.php?type=saveonlinerejection',JSON.stringify(dataForm.value)).subscribe(response => {
        if(response['status'] == 'success'){
          alertify.success('Record Inserted Successfully');
          this.isNew = false;
          this.dataForm.reset();
        }
      })
    }else{
      alertify.error('All feild Are Required');
    }
  }

}
