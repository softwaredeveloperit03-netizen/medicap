import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-online-approval',
  templateUrl: './online-approval.component.html'
})
export class OnlineApprovalComponent implements OnInit {
  isForm = false;
  constructor(private router:Router,private service:DataAccessService) { }

  ngOnInit(): void {
    this.getreport();
  }
  datalist = [];
  nofound = false;
  getreport(){
    this.service.get('rejection.php?type=onlinerejectionapproval').subscribe((response:any) => {
      this.datalist = response;
      if(this.datalist.length == 0){
        this.nofound = true;
      }
    })
  }
  closeform(){
    this.isForm = false;
  }
  submiting =false;
  approve(){
    this.submiting = true;
    const temp = new FormData();
    this.service.post('rejection.php?type=onlinerejectionapprove&id='+this.selecteddata.id,JSON.stringify(temp)).subscribe(response=>{
      this.getreport();
      alertify.success('Approved Successfully');
      this.submiting = false;
      this.isForm = false;
    })
  }
  selecteddata;
  viewform(index){
    this.selecteddata = this.datalist[index];
    this.isForm = true;
  }

}
