import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rawpacking-approval',
  templateUrl: './rawpacking-approval.component.html'
})
export class RawpackingApprovalComponent implements OnInit {
  isForm = false;
  constructor(private router:Router,private service:DataAccessService) { }

  ngOnInit(): void {
    this.getreport();
  }
  datalist;
  getreport(){
    this.service.get('rejection.php?type=rawpackingrejectionreport').subscribe(response => {
      this.datalist = response;
    })
  }
  closeform(){
    // this.router.navigateByUrl('/qa/rejection')
    this.isForm = false;
  }
  saverejection(value){
    if(value.valid){
      alert('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alert('All feild Are Required');
    }
  }
  selecteddata;
  viewapproval(index){
    this.selecteddata = this.datalist[index];
    this.isForm = true;
  }

}
