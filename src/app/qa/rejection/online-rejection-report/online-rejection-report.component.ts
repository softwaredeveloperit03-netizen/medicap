import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-online-rejection-report',
  templateUrl: './online-rejection-report.component.html'
})
export class OnlineRejectionReportComponent implements OnInit {
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
      alert('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alert('All feild Are Required');
    }
  }
  viewreport(){

  }

}
