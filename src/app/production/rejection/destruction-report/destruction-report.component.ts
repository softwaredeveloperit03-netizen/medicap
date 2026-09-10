import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-destruction-report',
  templateUrl: './destruction-report.component.html'
})
export class DestructionReportComponent implements OnInit {
  isForm = false;
  constructor(private router:Router, private service:DataAccessService) { }

  ngOnInit(): void {
    this.getonlinereport();
  }
  closeform(){
    this.router.navigateByUrl('rejection/dashboard')
  }
  datalist = [];
  getonlinereport(){
    this.service.get('rejection.php?type=onlinerejectionreport').subscribe((response:any) => {
      this.datalist = response;
    })
  }
  saverejection(value){
    if(value.valid){
      alertify.success('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alertify.error('All feild Are Required');
    }
  }
  viewreport(){

  }

}
