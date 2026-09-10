import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-approved',
  templateUrl: './approved.component.html',
  styleUrls: ['./approved.component.css'],
  providers:[DatePipe]
})
export class ApprovedComponent implements OnInit {

  results;
  
  results1;
  selectedResult;
  isView=false;
  state_name = '';
  state='';
  vendor_for = '';
  vendor_type = '';
  state_code='';
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getLogs();
    this.getstats();
  }
  getstats(){
    this.service.get('common.php?type=getStates').subscribe(response =>{
      this.results1=response;
    });
  }
  getLogs(){
    this.service.get('vendor.php?type=getApprovedVendors&state_code='+this.state_name + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for).subscribe(response =>{
      this.results=response;
        });
  }

  
  download(){
    this.service.open('vendor.php?type=downloadApprovedVendors&state_code='+this.state_name + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for);
   }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true; 
  }

}
