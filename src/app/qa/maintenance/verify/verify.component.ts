import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-verify',
  templateUrl: './verify.component.html',
  styleUrls: ['./verify.component.css']
})
export class VerifyComponent implements OnInit {
  isView = false;
  results;

  selectedResult = [];
  status='';
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getPendingMaintenances();
  }

  getPendingMaintenances(){
    this.service.get('engineering/maintenance.php?type=getCheckedMaintenances').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  save(data){
    this.service.post('engineering/maintenance.php?type=verifyMaintenance&status=' + this.status + '&id=' + this.selectedResult['id'],JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getPendingMaintenances();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
  // save(data){
  //   if(!data.valid){
  //     alertify.error('All fields are required!');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.service.post('engineering/maintenance.php?type=saveMaintenance',JSON.stringify(temp)).subscribe(response => {
  //     if(response['status'] == 'success'){
  //       alertify.success('Data Saved Successfully!');
  //       this.router.navigate(['/qms/maintenance']);
  //     }else{
  //       alertify.error('Failed an error occurd,Please try again!');
  //     }
  //   });
  // }
}
