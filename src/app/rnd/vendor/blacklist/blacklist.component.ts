import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-blacklist',
  templateUrl: './blacklist.component.html',
  styleUrls: ['./blacklist.component.css'],
  providers:[DatePipe]
})
export class BlacklistComponent implements OnInit {
  sShow = false;
  private isButtonVisible = true;
  results;
  selectedResult;
  isView=false;
  isView1;
  status;
  state = '';
  from_date = '';
  to_date = '';
  id;
  
  results1;
  
  state_code = '';
  state_name = '';
  vendor_for = '';
  vendor_type = '';

  isUser = false;
  isChecker = false;
  isApprover = false;
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getLogs();
    this.getstats();
  }


  download(){
    this.service.open('vendor.php?type=downloadBlacklistedVendors&state_code='+this.state_name + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for);
   }

  getstats(){
    this.service.get('common.php?type=getStates').subscribe(response =>{
      this.results1=response;
    });
  }
  getLogs(){
    this.service.get('vendor.php?type=getBlacklistedVendors&state_code='+this.state_name + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for).subscribe(response =>{
      this.results=response;
    });
  }


  view(index) {
    this.selectedResult = this.results[index];
    this.id = this.selectedResult['id'];
    this.isView = true; 
  }

  blacklist(index) {
    this.selectedResult = this.results[index];
    this.id = this.selectedResult['id'];
    this.status =this.selectedResult['status']

    if(this.status === "Blacklisted"){
    }
    else{    
      this.service.get('purchase/vendor.php?type=blacklistVendor&id='+ this.id).subscribe(response =>{
        this.getLogs();
        });
    }
 
  }
}
